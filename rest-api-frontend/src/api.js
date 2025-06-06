import axios from "axios";

const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  headers: {
    "Content-Type": "application/json",
  },
});

// Aggiungere il token automaticamente a ogni richiesta autenticata
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    console.error("Errore nella richiesta:", error);
    return Promise.reject(error);
  }
);

// Log degli errori per debugging
api.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error("Errore nella risposta API:", error.response || error);
    alert("Errore API: " + (error.response?.data?.message || "Errore sconosciuto"));
    return Promise.reject(error);
  }
);

export default api;

