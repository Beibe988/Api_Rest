import React, { useEffect, useState } from "react";
import api from "../api";
import { motion } from "framer-motion";

function SerieTv() {
  const [series, setSeries] = useState([]);
  const [title, setTitle] = useState("");
  const [year, setYear] = useState("");
  const [description, setDescription] = useState("");
  const [category, setCategory] = useState("");
  const [language, setLanguage] = useState("English");
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);
  const [editSerie, setEditSerie] = useState(null);
  const [categories, setCategories] = useState([]);

  const languages = ["English", "Spanish", "French", "German", "Italian", "Japanese"];

  useEffect(() => {
    fetchSeries();
    fetchCategories();
  }, []);

  const fetchSeries = async () => {
    try {
      const response = await api.get("/series", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setSeries(response.data);
    } catch (err) {
      setError("Errore nel caricamento delle serie TV");
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.get("/categories", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setCategories(response.data);
    } catch (err) {
      setError("Errore nel caricamento delle categorie");
    }
  };

  const handleAddSerie = async (e) => {
    e.preventDefault();
    try {
      await api.post(
        "/series",
        { title, year, description, category, language },
        { headers: { Authorization: `Bearer ${localStorage.getItem("token")}` } }
      );
      setSuccess("Serie aggiunta con successo!");
      setTitle("");
      setYear("");
      setDescription("");
      setCategory("");
      setLanguage("English");
      fetchSeries();
    } catch (err) {
      setError("Errore durante l'aggiunta della serie.");
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm("Vuoi eliminare questa serie?")) {
      try {
        await api.delete(`/series/${id}`, {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });
        fetchSeries();
      } catch (err) {
        alert("Errore durante l'eliminazione della serie.");
      }
    }
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    try {
      await api.put(`/series/${editSerie.id}`, editSerie, {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setEditSerie(null);
      fetchSeries();
    } catch (err) {
      alert("Errore durante l'aggiornamento della serie.");
    }
  };

  const hoverEffect = {
    whileHover: {
      rotateY: 360,
      backgroundColor: "#3b82f6",
      color: "#ffffff",
      transition: { duration: 0.6 },
    },
  };

  return (
    <div className="p-4">
      <div className="max-w-xl mx-auto bg-white p-4 rounded shadow-md">
        <h3 className="text-xl font-semibold mb-2">Aggiungi una nuova Serie</h3>
        {success && <p className="text-green-500">{success}</p>}
        {error && <p className="text-red-500">{error}</p>}
        <form onSubmit={handleAddSerie} className="flex flex-col gap-2">
          <input type="text" className="p-2 border rounded" placeholder="Titolo" value={title} onChange={(e) => setTitle(e.target.value)} required />
          <input type="number" className="p-2 border rounded" placeholder="Anno di uscita" value={year} onChange={(e) => setYear(e.target.value)} required />
          <textarea className="p-2 border rounded" placeholder="Descrizione" value={description} onChange={(e) => setDescription(e.target.value)} required />
          <select className="p-2 border rounded" value={category} onChange={(e) => setCategory(e.target.value)} required>
            <option value="">Seleziona Categoria</option>
            {categories.map((cat) => (
              <option key={cat.id} value={cat.name}>{cat.name}</option>
            ))}
          </select>
          <select className="p-2 border rounded" value={language} onChange={(e) => setLanguage(e.target.value)} required>
            {languages.map((lang) => <option key={lang} value={lang}>{lang}</option>)}
          </select>
          <button className="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Aggiungi</button>
        </form>
      </div>

      <h2 className="text-xl font-bold my-6">Lista Serie</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {series.map((serie) => (
          <motion.div
            key={serie.id}
            whileHover={hoverEffect.whileHover}
            className="bg-white text-black border p-4 rounded shadow transition-colors hover:text-white"
          >
            <h3 className="text-lg font-semibold">{serie.title}</h3>
            <p className="text-gray-700">{serie.description}</p>
            <p className="text-sm text-gray-700"><strong>Categoria:</strong> {serie.category}</p>
            <p className="text-sm text-gray-700"><strong>Lingua:</strong> {serie.language}</p>
            <p className="text-sm text-gray-700"><strong>Episodi:</strong> {serie.episodes_count ?? 0}</p>
            <div className="flex gap-2 mt-2">
              <button onClick={() => setEditSerie(serie)} className="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">Modifica</button>
              <button onClick={() => handleDelete(serie.id)} className="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Elimina</button>
            </div>
          </motion.div>
        ))}
      </div>

      {editSerie && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
          <div className="bg-white p-4 rounded shadow-lg w-1/2">
            <h2 className="text-xl font-bold mb-4">Modifica Serie</h2>
            <form onSubmit={handleUpdate} className="flex flex-col gap-2">
              <input className="p-2 border rounded" type="text" value={editSerie.title} onChange={(e) => setEditSerie({ ...editSerie, title: e.target.value })} required />
              <input className="p-2 border rounded" type="number" value={editSerie.year} onChange={(e) => setEditSerie({ ...editSerie, year: e.target.value })} required />
              <textarea className="p-2 border rounded" value={editSerie.description} onChange={(e) => setEditSerie({ ...editSerie, description: e.target.value })} required />
              <select className="p-2 border rounded" value={editSerie.category} onChange={(e) => setEditSerie({ ...editSerie, category: e.target.value })} required>
                <option value="">Seleziona Categoria</option>
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.name}>{cat.name}</option>
                ))}
              </select>
              <input className="p-2 border rounded" type="text" value={editSerie.language} onChange={(e) => setEditSerie({ ...editSerie, language: e.target.value })} required />
              <div className="flex gap-2">
                <button type="submit" className="bg-blue-500 text-white p-2 rounded hover:bg-blue-700">Salva</button>
                <button type="button" onClick={() => setEditSerie(null)} className="bg-gray-500 text-white p-2 rounded hover:bg-gray-700">Annulla</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default SerieTv;




