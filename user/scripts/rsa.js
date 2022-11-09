
async function importPublicKeyAndEncrypt(publicKey,plaintext='') {
    try {
        const pub = await importPublicKey(publicKey);
        const encrypted = await encryptRSA(pub, new TextEncoder().encode(plaintext));
        const encryptedBase64 = Pikajs.arrayBufferToBase64(encrypted);
        return encryptedBase64.replace(/(.{64})/g, "$1"); 
    } catch(error) {
        console.log(error);
    }
    return '';
}

async function importPublicKey(spkiPem) {
  const binaryDer = Pikajs.base64ToArrayBuffer(spkiPem);
    return await window.crypto.subtle.importKey(
        "spki",
        binaryDer,
        {
            name: "RSA-OAEP",
            hash: "SHA-256",
        },
        true,
        ["encrypt"]
    );
}

async function encryptRSA(key, plaintext) {
    let encrypted = await window.crypto.subtle.encrypt({
            name: "RSA-OAEP"
        },
        key,
        plaintext
    );
    return encrypted;
}
