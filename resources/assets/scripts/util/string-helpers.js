export function ucFirst(str) {
  if (!str) return "";
  return str.charAt(0).toUpperCase() + str.substr(1).toLowerCase();
}

export function ucWords(str) {
  if (!str) return "";
  return str
    .split(" ")
    .map((p) => ucFirst(p))
    .join(" ");
}

export function deslugify(str) {
  const parts = str.split(/[-_]/g);
  return parts.join(" ");
}
