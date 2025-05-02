import React, { useEffect, useState } from "react";
import api from "../api";
import { motion } from "framer-motion";

function Movies() {
  const [movies, setMovies] = useState([]);
  const [editMovie, setEditMovie] = useState(null);
  const [categories, setCategories] = useState([]);

  // Form states
  const [title, setTitle] = useState("");
  const [year, setYear] = useState("");
  const [videoLink, setVideoLink] = useState("");
  const [category, setCategory] = useState("");
  const [language, setLanguage] = useState("English");
  const [description, setDescription] = useState("");
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  const languages = ["English", "Spanish", "French", "German", "Italian", "Japanese"];

  useEffect(() => {
    fetchMovies();
    fetchCategories();
  }, []);

  const fetchMovies = async () => {
    try {
      const response = await api.get("/movies", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setMovies(response.data);
    } catch (error) {
      setError("Failed to fetch movies.");
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.get("/categories", {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
      });
      setCategories(response.data);
    } catch (error) {
      setError("Errore nel caricamento delle categorie");
    }
  };

  const handleAddMovie = async (e) => {
    e.preventDefault();
    try {
      await api.post(
        "/movies",
        { title, year, video_link: videoLink, category, language, description },
        { headers: { Authorization: `Bearer ${localStorage.getItem("token")}` } }
      );
      setSuccess("Movie added successfully!");
      setTitle("");
      setYear("");
      setVideoLink("");
      setCategory("");
      setLanguage("English");
      setDescription("");
      fetchMovies();
    } catch {
      setError("Failed to add movie.");
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm("Delete this movie?")) {
      try {
        await api.delete(`/movies/${id}`, {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });
        setMovies(movies.filter((m) => m.id !== id));
      } catch {
        alert("Delete failed.");
      }
    }
  };

  const handleUpdate = async (e) => {
    e.preventDefault();
    try {
      await api.put(
        `/movies/${editMovie.id}`,
        editMovie,
        { headers: { Authorization: `Bearer ${localStorage.getItem("token")}` } }
      );
      setEditMovie(null);
      fetchMovies();
    } catch {
      alert("Update failed.");
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
        <h2 className="text-xl font-bold mb-4">Add Movie</h2>
        {success && <p className="text-green-500">{success}</p>}
        {error && <p className="text-red-500">{error}</p>}
        <form onSubmit={handleAddMovie} className="flex flex-col gap-2">
          <input type="text" placeholder="Title" value={title} onChange={(e) => setTitle(e.target.value)} className="p-2 border rounded" required />
          <input type="number" placeholder="Year" value={year} onChange={(e) => setYear(e.target.value)} className="p-2 border rounded" required />
          <input type="text" placeholder="Video Link" value={videoLink} onChange={(e) => setVideoLink(e.target.value)} className="p-2 border rounded" />
          <select value={category} onChange={(e) => setCategory(e.target.value)} className="p-2 border rounded" required>
            <option value="">Seleziona Categoria</option>
            {categories.map((cat) => <option key={cat.id} value={cat.name}>{cat.name}</option>)}
          </select>
          <select value={language} onChange={(e) => setLanguage(e.target.value)} className="p-2 border rounded">
            {languages.map(lang => <option key={lang} value={lang}>{lang}</option>)}
          </select>
          <textarea placeholder="Description" value={description} onChange={(e) => setDescription(e.target.value)} className="p-2 border rounded" required />
          <button className="bg-blue-500 text-white p-2 rounded">Add Movie</button>
        </form>
      </div>

      <h2 className="text-xl font-bold my-6">Movies List</h2>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {movies.map((movie) => (
          <motion.div
            key={movie.id}
            whileHover={hoverEffect.whileHover}
            className="bg-white text-black border p-4 rounded shadow-lg transition-colors hover:text-white"
          >
            {movie.video_link ? (
              movie.video_link.includes("youtube.com") || movie.video_link.includes("youtu.be") ? (
                <iframe
                  src={`https://www.youtube.com/embed/${new URL(movie.video_link).searchParams.get("v")}`}
                  className="w-full h-40"
                  frameBorder="0"
                  allowFullScreen
                />
              ) : (
                <video src={movie.video_link} controls className="w-full h-40 object-cover" />
              )
            ) : <p>No preview</p>}

            <h3 className="text-lg font-semibold mt-2">{movie.title}</h3>
            <p>{movie.description}</p>
            <p><strong>Year:</strong> {movie.year}</p>
            <p><strong>Category:</strong> {movie.category}</p>
            <p><strong>Language:</strong> {movie.language}</p>

            <div className="flex gap-2 mt-2">
              <button onClick={() => setEditMovie(movie)} className="bg-yellow-500 px-2 py-1 rounded text-white">Edit</button>
              <button onClick={() => handleDelete(movie.id)} className="bg-red-500 px-2 py-1 rounded text-white">Delete</button>
            </div>
          </motion.div>
        ))}
      </div>

      {editMovie && (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
          <div className="bg-white p-4 rounded shadow-lg w-1/2">
            <h2 className="text-xl font-bold">Edit Movie</h2>
            <form onSubmit={handleUpdate} className="flex flex-col gap-2">
              <input type="text" value={editMovie.title} onChange={(e) => setEditMovie({ ...editMovie, title: e.target.value })} className="p-2 border rounded" required />
              <input type="number" value={editMovie.year} onChange={(e) => setEditMovie({ ...editMovie, year: e.target.value })} className="p-2 border rounded" required />
              <input type="text" value={editMovie.video_link} onChange={(e) => setEditMovie({ ...editMovie, video_link: e.target.value })} className="p-2 border rounded" />
              <textarea value={editMovie.description} onChange={(e) => setEditMovie({ ...editMovie, description: e.target.value })} className="p-2 border rounded" required />
              <select value={editMovie.category} onChange={(e) => setEditMovie({ ...editMovie, category: e.target.value })} className="p-2 border rounded" required>
                <option value="">Seleziona Categoria</option>
                {categories.map((cat) => <option key={cat.id} value={cat.name}>{cat.name}</option>)}
              </select>
              <input type="text" value={editMovie.language} onChange={(e) => setEditMovie({ ...editMovie, language: e.target.value })} className="p-2 border rounded" />
              <div className="flex gap-2">
                <button type="submit" className="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
                <button type="button" onClick={() => setEditMovie(null)} className="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default Movies;




