import React, { useState } from "react";
//import { useNavigate } from "react-router-dom";
import api from "../api";

function Register() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirm, setPasswordConfirm] = useState("");
  const [success, setSuccess] = useState(null);
  const [error, setError] = useState(null);

  const handleRegister = async (e) => {
    e.preventDefault();
    try {
      await api.post("/register", { name, email, password, password_confirmation: passwordConfirm });
      setSuccess("Registration successful! Redirecting...");
      setTimeout(() => (window.location.href = "/login"), 2000);
    } catch (error) {
      if (error.response?.data?.errors) {
        const firstError = Object.values(error.response.data.errors)[0][0];
        setError(firstError);
      } else {
        setError("Registration failed. Try again.");
      }      
    }
  };

  return (
    <div className="p-4">
      <h2 className="text-xl font-bold">Register</h2>
      {success && <p className="text-green-500">{success}</p>}
      {error && <p className="text-red-500">{error}</p>}
      <form onSubmit={handleRegister} className="flex flex-col gap-2">
        <input className="p-2 border rounded" type="text" placeholder="Name" value={name} onChange={(e) => setName(e.target.value)} required />
        <input className="p-2 border rounded" type="email" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} required />
        <input className="p-2 border rounded" type="password" placeholder="Password" value={password} onChange={(e) => setPassword(e.target.value)} required />
        <input className="p-2 border rounded" type="password" placeholder="Conferma Password" value={passwordConfirm} onChange={(e) => setPasswordConfirm(e.target.value)} required />
        <button className="bg-green-500 text-white p-2 rounded hover:bg-green-700">Register</button>
      </form>
    </div>
  );
}

export default Register;
