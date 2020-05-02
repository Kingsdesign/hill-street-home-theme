const Cookies = window.Cookies;
const data = window.main_data;

const cookieName = data.cookie_name;

console.log(data);

const cookieData = (() => {
  try {
    return JSON.parse(Cookies.get(cookieName));
  } catch (e) {
    return null;
  }
})();

export default function scData(key = null) {
  if (key === null) return cookieData;
  if (!cookieData) return null;
  if (typeof cookieData[key] !== `undefined`) return cookieData[key];
  return null;
}
