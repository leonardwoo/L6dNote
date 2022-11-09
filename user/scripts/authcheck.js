"use strict";

window.addEventListener('load', (event) => {
  const user = localStorage.getItem('user');
  const redirectUrl = "login.htm";
  const userJO = JSON.parse(user);
  if (userJO == null) {
    window.location.href = redirectUrl;
  }
  var token = userJO.token;
  var payload = ParseJwtPayload(token);
  if (payload == null || ((payload.iat + userJO.expire) * 1000) < Date.parse(new Date())) {
    window.location.href = redirectUrl;
  }
});
