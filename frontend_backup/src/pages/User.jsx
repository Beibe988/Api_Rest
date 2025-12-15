import React, { useEffect, useState } from "react";
import api from "../api";

function User() {
  const [users, setUsers] = useState([]);
  const [formData, setFormData] = useState({
    name: "",
    surname: "",
    birth_year: "",
    country: "",
    language: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "User",
  });
  const [editUser, setEditUser] = useState(null);
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    try {
      const response = await api.get("/users");
      setUsers(response.data);
    } catch (err) {
      setError("Errore nel caricamento degli utenti");
    }
  };

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const dataToSend = { ...formData };

    if (!dataToSend.password) delete dataToSend.password;
    if (!dataToSend.password_confirmation) delete dataToSend.password_confirmation;

    try {
      if (editUser) {
        await api.put(`/users/${editUser.id}`, dataToSend);
        setSuccess("Utente aggiornato con successo!");
        setEditUser(null);
      } else {
        await api.post("/users", dataToSend);
        setSuccess("Utente aggiunto con successo!");
      }
      resetForm();
      fetchUsers();
    } catch (err) {
      setError("Errore durante il salvataggio");
    }
  };

  const handleDelete = async (id) => {
    if (window.confirm("Sei sicuro di voler eliminare questo utente?")) {
      try {
        await api.delete(`/users/${id}`);
        setSuccess("Utente eliminato con successo!");
        fetchUsers();
      } catch (err) {
        setError("Errore durante l'eliminazione");
      }
    }
  };

  const resetForm = () => {
    setFormData({
      name: "",
      surname: "",
      birth_year: "",
      country: "",
      language: "",
      email: "",
      password: "",
      password_confirmation: "",
      role: "User",
    });
    setEditUser(null);
    setSuccess(null);
    setError(null);
  };

  return (
    <div className="p-4">
      <h2 className="text-xl font-bold mb-4">Gestione Utenti</h2>
      {success && <p className="text-green-600">{success}</p>}
      {error && <p className="text-red-600">{error}</p>}

      {/* Form compatto */}
      <form onSubmit={handleSubmit} className="mb-6 max-w-md mx-auto flex flex-col gap-2 bg-white p-4 shadow rounded">
        <input name="name" placeholder="Nome" value={formData.name} onChange={handleChange} className="p-2 border rounded" required />
        <input name="surname" placeholder="Cognome" value={formData.surname} onChange={handleChange} className="p-2 border rounded" required />
        <input name="birth_year" type="number" placeholder="Anno di nascita" value={formData.birth_year} onChange={handleChange} className="p-2 border rounded" required />
        <input name="country" placeholder="Paese" value={formData.country} onChange={handleChange} className="p-2 border rounded" required />
        <input name="language" placeholder="Lingua" value={formData.language} onChange={handleChange} className="p-2 border rounded" required />
        <input name="email" type="email" placeholder="Email" value={formData.email} onChange={handleChange} className="p-2 border rounded" required />
        <input name="password" type="password" placeholder="Password (opzionale)" value={formData.password} onChange={handleChange} className="p-2 border rounded" />
        <input name="password_confirmation" type="password" placeholder="Conferma Password (opzionale)" value={formData.password_confirmation} onChange={handleChange} className="p-2 border rounded" />
        <select name="role" value={formData.role} onChange={handleChange} className="p-2 border rounded">
          <option value="User">User</option>
          <option value="Admin">Admin</option>
        </select>
        <div className="flex gap-2">
          <button type="submit" className="bg-blue-600 text-white p-2 rounded hover:bg-blue-800">{editUser ? "Aggiorna Utente" : "Aggiungi Utente"}</button>
          {editUser && (
            <button type="button" onClick={resetForm} className="bg-gray-400 text-white p-2 rounded hover:bg-gray-600">Annulla modifica</button>
          )}
        </div>
      </form>

      {/* Lista utenti */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-6xl mx-auto">
        {users.map((user) => (
          <div key={user.id} className="group perspective">
            <div className="relative preserve-3d group-hover:rotate-y-180 transition-transform duration-700 bg-white border rounded shadow p-6 min-h-[200px] flex flex-col justify-center items-center">
              
              {/* Fronte */}
              <div className="absolute backface-hidden flex flex-col justify-center items-center w-full h-full">
                <p className="text-lg font-semibold text-black">{user.name} {user.surname}</p>
                <p className="text-sm text-gray-600">{user.email}</p>
              </div>

              {/* Retro */}
              <div className="absolute backface-hidden rotate-y-180 flex flex-col justify-center items-center w-full h-full">
                <p className="text-md font-semibold text-black">{user.country}</p>
                <p className="text-sm text-gray-600">{user.language}</p>
                <p className="text-sm text-gray-600">{user.birth_year}</p>
                <p className="text-sm text-gray-600">{user.role}</p>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default User;













