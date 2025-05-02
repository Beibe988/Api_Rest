import React, { useEffect, useState } from "react";
import api from "../api";
import { motion } from "framer-motion";

function Episode() {
  const [episodes, setEpisodes] = useState([]);
  const [series, setSeries] = useState([]);
  const [languages, setLanguages] = useState([]);
  const [formData, setFormData] = useState({
    serie_tv_id: "",
    title: "",
    description: "",
    video_url: "",
    season: "",
    language: "",
    episode_number: ""
  });
  const [editEpisode, setEditEpisode] = useState(null);
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchEpisodes();
    fetchSeries();
    fetchLanguages();
  }, []);

  const fetchEpisodes = async () => {
    try {
      const response = await api.get("/episodes");
      setEpisodes(response.data);
    } catch (err) {
      setError("Errore nel caricamento degli episodi");
    }
  };

  const fetchSeries = async () => {
    try {
      const response = await api.get("/series");
      setSeries(response.data);
    } catch (err) {
      console.error("Errore nel recupero delle serie TV", err);
      setError("Errore nel recupero delle serie TV");
    }
  };

  const fetchLanguages = async () => {
    try {
      const response = await api.get("/languages");
      setLanguages(response.data);
    } catch (err) {
      console.error("Errore nel recupero delle lingue", err);
      setError("Errore nel recupero delle lingue");
    }
  };

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      if (editEpisode) {
        await api.put(`/episodes/${editEpisode.id}`, formData);
        setSuccess("Episodio aggiornato con successo!");
      } else {
        await api.post("/episodes", formData);
        setSuccess("Episodio aggiunto con successo!");
      }
      resetForm();
      fetchEpisodes();
    } catch (err) {
      setError("Errore durante il salvataggio dell'episodio");
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm("Sei sicuro di voler eliminare questo episodio?")) {
      try {
        await api.delete(`/episodes/${id}`);
        setSuccess("Episodio eliminato con successo!");
        fetchEpisodes();
      } catch (err) {
        setError("Errore durante l'eliminazione");
      }
    }
  };

  const resetForm = () => {
    setFormData({
      serie_tv_id: "",
      title: "",
      description: "",
      video_url: "",
      season: "",
      language: "",
      episode_number: ""
    });
    setEditEpisode(null);
    setSuccess(null);
    setError(null);
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
      <h2 className="text-xl font-bold mb-4">Gestione Episodi</h2>

      {success && <p className="text-green-600">{success}</p>}
      {error && <p className="text-red-600">{error}</p>}

      <form onSubmit={handleSubmit} className="mb-6 max-w-xl mx-auto flex flex-col gap-2 bg-white p-4 shadow rounded">
        <select name="serie_tv_id" value={formData.serie_tv_id} onChange={handleChange} className="p-2 border rounded" required>
          <option value="">Seleziona Serie TV</option>
          {series.map((serie) => (
            <option key={serie.id} value={serie.id}>{serie.title}</option>
          ))}
        </select>
        <input name="title" placeholder="Titolo episodio" value={formData.title} onChange={handleChange} className="p-2 border rounded" required />
        <input name="season" type="number" placeholder="Stagione" value={formData.season} onChange={handleChange} className="p-2 border rounded" required />
        <input name="episode_number" type="number" placeholder="Numero Episodio" value={formData.episode_number} onChange={handleChange} className="p-2 border rounded" required />
        <select name="language" value={formData.language} onChange={handleChange} className="p-2 border rounded" required>
          <option value="">Seleziona lingua</option>
          {languages.map((lang) => (
            <option key={lang.id} value={lang.name}>{lang.name}</option>
          ))}
        </select>
        <textarea name="description" placeholder="Descrizione" value={formData.description} onChange={handleChange} className="p-2 border rounded" />
        <input name="video_url" placeholder="Link al video (es. YouTube)" value={formData.video_url} onChange={handleChange} className="p-2 border rounded" />
        <div className="flex gap-2">
          <button type="submit" className="bg-blue-600 text-white p-2 rounded hover:bg-blue-800">
            {editEpisode ? "Aggiorna Episodio" : "Aggiungi Episodio"}
          </button>
          {editEpisode && (
            <button type="button" onClick={resetForm} className="bg-gray-400 text-white p-2 rounded hover:bg-gray-600">
              Annulla modifica
            </button>
          )}
        </div>
      </form>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {episodes.map((ep) => (
          <motion.div
            key={ep.id}
            whileHover={hoverEffect.whileHover}
            className="bg-white text-black border p-4 rounded shadow transition-colors hover:text-white"
          >
            <p className="font-semibold">{ep.title}</p>
            <p className="text-sm">Stagione: {ep.season}</p>
            <p className="text-sm">Episodio: {ep.episode_number}</p>
            <p className="text-sm">Lingua: {ep.language}</p>
            <p className="text-sm">{ep.description}</p>
            {ep.video_url && (
              <a href={ep.video_url} target="_blank" rel="noopener noreferrer" className="text-green-500 underline">
                Guarda Video
              </a>
            )}
            <div className="flex gap-2 mt-2">
              <button onClick={() => {
                setEditEpisode(ep);
                setFormData({
                  serie_tv_id: ep.serie_tv_id,
                  title: ep.title,
                  description: ep.description,
                  video_url: ep.video_url || "",
                  season: ep.season || "",
                  language: ep.language || "",
                  episode_number: ep.episode_number || ""
                });
              }} className="text-yellow-600 hover:underline">
                Modifica
              </button>
              <button onClick={() => handleDelete(ep.id)} className="text-red-600 hover:underline">
                Elimina
              </button>
            </div>
          </motion.div>
        ))}
      </div>
    </div>
  );
}

export default Episode;







