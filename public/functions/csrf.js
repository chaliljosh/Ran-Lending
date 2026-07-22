/**
 * csrf.js — kailangan i-load ito BAGO ang ibang scripts (login.js, register.js,
 * forgot-password.js, atbp.) na gumagawa ng POST fetch() papunta sa Laravel.
 *
 * Paano gamitin sa fetch():
 *   fetch('/api/register', {
 *     method: 'POST',
 *     credentials: 'same-origin',
 *     headers: { 'X-XSRF-TOKEN': getCsrfToken() },
 *     body: formData
 *   })
 *
 * Paalala: kailangang naka-visit muna ang user sa isang Laravel web route
 * (gaya ng /login, /register) bago meron itong XSRF-TOKEN cookie — kaya
 * automatic na 'to gagana kasi galing na sa Laravel routes ang pages natin.
 */

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
  return null;
}

function getCsrfToken() {
  return getCookie('XSRF-TOKEN');
}