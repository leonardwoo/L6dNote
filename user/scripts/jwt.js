function ParseJwtPayload(token = "") {
  if (token == null || token.length == 0) {
    return null;
  }

  const jwt_part = token.split('.');

  let payloadBase64 = jwt_part[1].replace(/-/g, '+').replace(/_/g, '/');
  let payload = Pikajs.base64Decode(payloadBase64).split('').map((c) => {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join('');

  return JSON.parse(decodeURIComponent(payload));
}
