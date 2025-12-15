import React, { useEffect, useState } from "react";
import { motion } from "framer-motion";
import api from "../api";

function Categories() {
  const [categories, setCategories] = useState([]);
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [editCategory, setEditCategory] = useState(null);
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const response = await api.get("/categories");
      setCategories(response.data);
    } catch (err) {
      setError("Errore nel caricamento delle categorie");
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const payload = { name, description };
    try {
      if (editCategory) {
        await api.put(`/categories/${editCategory.id}`, payload);
        setSuccess("Categoria aggiornata con successo!");
      } else {
        await api.post("/categories", payload);
        setSuccess("Categoria aggiunta con successo!");
      }
      resetForm();
      fetchCategories();
    } catch (err) {
      setError("Errore durante il salvataggio");
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm("Sei sicuro di voler eliminare questa categoria?")) {
      try {
        await api.delete(`/categories/${id}`);
        setSuccess("Categoria eliminata con successo!");
        fetchCategories();
      } catch (err) {
        setError("Errore durante l'eliminazione");
      }
    }
  };

  const resetForm = () => {
    setName("");
    setDescription("");
    setEditCategory(null);
    setSuccess(null);
    setError(null);
  };

  return (
    <div className="p-4">
      <h2 className="text-xl font-bold mb-4">Gestione Categorie</h2>

      {success && <p className="text-green-600">{success}</p>}
      {error && <p className="text-red-600">{error}</p>}

      <form onSubmit={handleSubmit} className="mb-6 max-w-md mx-auto flex flex-col gap-2 bg-white p-4 shadow rounded">
        <input
          type="text"
          placeholder="Nome categoria"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
          className="p-2 border rounded"
        />
        <textarea
          placeholder="Descrizione"
          value={description}
          onChange={(e) => setDescription(e.target.value)}
          className="p-2 border rounded"
        ></textarea>
        <div className="flex gap-2">
          <button
            type="submit"
            className="bg-blue-600 text-white p-2 rounded hover:bg-blue-800"
          >
            {editCategory ? "Aggiorna Categoria" : "Aggiungi Categoria"}
          </button>
          {editCategory && (
            <button
              type="button"
              onClick={resetForm}
              className="bg-gray-400 text-white p-2 rounded hover:bg-gray-600"
            >
              Annulla modifica
            </button>
          )}
        </div>
      </form>

      <ul className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-6xl mx-auto">
        {categories.map((cat, i) => (
          <motion.li
            key={cat.id}
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: i * 0.1, duration: 0.6, ease: "easeOut" }}
            className="p-3 bg-gray-100 border rounded group hover:shadow-lg transition-all duration-700 ease-in-out"
          >
            <div className="w-full">
              <motion.p
                initial={{ textAlign: "left", fontWeight: 500 }}
                whileHover={{ textAlign: "center", fontWeight: 700 }}
                transition={{ duration: 10, ease: "easeInOut" }}
                className="transition-all"
              >
                {cat.name}
              </motion.p>
              <p className="text-sm text-gray-600 text-center mt-1">{cat.description}</p>
            </div>
            <div className="flex justify-center gap-4 mt-2">
              <button
                onClick={() => {
                  setEditCategory(cat);
                  setName(cat.name);
                  setDescription(cat.description);
                }}
                className="text-yellow-600 hover:underline"
              >
                Modifica
              </button>
              <button
                onClick={() => handleDelete(cat.id)}
                className="text-red-600 hover:underline"
              >
                Elimina
              </button>
            </div>
          </motion.li>
        ))}
      </ul>
    </div>
  );
}

export default Categories;


    






