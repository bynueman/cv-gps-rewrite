export function productInquiryUrl(contact: string | null | undefined, brand: string, name: string, lang: "id" | "en"): string | null {
  const number = (contact || "").replace(/\D/g, "");
  if (!/^[1-9]\d{7,14}$/.test(number)) return null;
  const message = (lang === "id" ? "Halo tim GPS FOOD, saya ingin bertanya mengenai produk " : "Hello GPS FOOD, I would like to ask about ") + brand + " " + name + ".";
  return "https://wa.me/" + number + "?text=" + encodeURIComponent(message);
}
