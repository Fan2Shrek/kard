const getSSRValue = (CSRValue, SSRValue) => typeof window === "undefined" ? SSRValue : CSRValue;

export const API_URL = getSSRValue(process.env.NEXT_PUBLIC_API_URL, process.env.API_URL);
