<?php

class Libravatar {
    private const BASE_URL = "https://secureroot.libravatar.org/avatar";
    private const BASE_HTTP_URL = "http://cdn.libravatar.org/avatar";

    private $isSecure = true;

    /**
     * 设置是否使用安全连接 (HTTPS)
     */
    public function setSecure(bool $secure): void {
        $this->isSecure = $secure;
    }

    /**
     * 获取基础 URL
     */
    private function getBaseUrl(): string {
        return $this->isSecure ? self::BASE_URL : self::BASE_HTTP_URL;
    }

    /**
     * 核心方法：根据 Email 或 OpenID 生成头像 URL
     * * @param string $id Email 或 OpenID URL
     * @param array $options 额外参数，如 ['s' => 80, 'd' => 'mm']
     */
    public function generate(string $id, array $options = []): string {
        $id = trim($id);
        
        if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
            return $this->fromEmail($id, $options);
        } else {
            return $this->fromOpenID($id, $options);
        }
    }

    /**
     * 通过 Email 生成 URL
     */
    public function fromEmail(string $email, array $options = []): string {
        $email = strtolower(trim($email));
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $this->buildUrl(md5($email), $options);
        }

        $domain = $parts[1];
        $hash = md5($email);

        // 尝试通过 DNS SRV 寻找自定义服务器
        $srvTarget = $this->srvLookup($domain);
        if ($srvTarget) {
            return $this->buildCustomUrl($srvTarget, $hash, $options);
        }

        return $this->buildUrl($hash, $options);
    }

    /**
     * 通过 OpenID 生成 URL
     */
    public function fromOpenID(string $openid, array $options = []): string {
        // 规范化 OpenID URL
        $parsed = parse_url($openid);
        if (!$parsed || !isset($parsed['host'])) {
            return $this->buildUrl(md5($openid), $options);
        }

        $scheme = $parsed['scheme'] ?? 'http';
        $host = strtolower($parsed['host']);
        $path = $parsed['path'] ?? '/';
        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

        // 移除默认端口
        if (isset($parsed['port'])) {
            if (($scheme === 'http' && $parsed['port'] == 80) || ($scheme === 'https' && $parsed['port'] == 443)) {
                // 默认端口，不作处理
            } else {
                $host .= ':' . $parsed['port'];
            }
        }

        $normalized = $scheme . '://' . $host . $path . $query;
        $hash = sha1($normalized);

        // 尝试通过 DNS SRV 寻找自定义服务器
        $domain = $parsed['host'];
        $srvTarget = $this->srvLookup($domain);
        if ($srvTarget) {
            return $this->buildCustomUrl($srvTarget, $hash, $options);
        }

        return $this->buildUrl($hash, $options);
    }

    /**
     * 查询 DNS SRV 记录
     */
    private function srvLookup(string $domain): ?string {
        $service = $this->isSecure ? "_avatars-sec._tcp." : "_avatars._tcp.";
        $query = $service . $domain;

        // 在 Windows 环境下 dns_get_record 的 SRV 支持可能有限，但在 Linux 下运作良好
        $records = @dns_get_record($query, DNS_SRV);

        if (!$records || count($records) === 0) {
            return null;
        }

        // 简单对权重和优先级进行排序（这里取第一个，Go 代码中使用了默认的权重选择算法，PHP 简化取优先级最高的）
        usort($records, function($a, $b) {
            if ($a['pri'] == $b['pri']) {
                return $b['weight'] <=> $a['weight']; // 权重大的优先
            }
            return $a['pri'] <=> $b['pri']; // 优先级小的优先
        });

        $best = $records[0];
        $target = rtrim($best['target'], '.');
        $port = $best['port'];

        $scheme = $this->isSecure ? 'https' : 'http';
        
        // 如果是默认端口，则忽略端口号
        if (($scheme === 'http' && $port == 80) || ($scheme === 'https' && $port == 443)) {
            return "{$scheme}://{$target}/avatar";
        }

        return "{$scheme}://{$target}:{$port}/avatar";
    }

    /**
     * 拼接官方默认域名的 URL
     */
    private function buildUrl(string $hash, array $options): string {
        $url = $this->getBaseUrl() . '/' . $hash;
        if (!empty($options)) {
            $url .= '?' . http_build_query($options);
        }
        return $url;
    }

    /**
     * 拼接自定义 SRV 域名的 URL
     */
    private function buildCustomUrl(string $baseUrl, string $hash, array $options): string {
        $url = $baseUrl . '/' . $hash;
        if (!empty($options)) {
            $url .= '?' . http_build_query($options);
        }
        return $url;
    }
}

?>