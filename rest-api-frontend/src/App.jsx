import React from "react";
import { BrowserRouter as Router, Route, Routes, Navigate } from "react-router-dom";

// Componenti
import Navbar from "./components/Navbar";
import Home from "./pages/Home";
import Login from "./pages/Login";
import Register from "./pages/Register";
import Movies from "./pages/Movies";
import SerieTv from "./pages/SerieTv";
import Episode from "./pages/Episode";
import Category from "./pages/Category";
import User from "./pages/User";

function ProtectedRoute({ children }) {
  const token = localStorage.getItem("token");
  return token ? children : <Navigate to="/login" />;
}

function App() {
  const user = JSON.parse(localStorage.getItem("user"));
  const role = user?.role;

  return (
    <Router>
      <Navbar />

      <div className="container mx-auto mt-6 p-4 bg-gray-600 rounded-lg shadow-md">
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />

          {/* Accesso User + Admin */}
          <Route path="/movies" element={<ProtectedRoute><Movies /></ProtectedRoute>} />
          <Route path="/series" element={<ProtectedRoute><SerieTv /></ProtectedRoute>} />
          <Route path="/episodes" element={<ProtectedRoute><Episode /></ProtectedRoute>} />

          {/* Accesso solo Admin */}
          {role === "Admin" && (
            <>
              <Route path="/categories" element={<ProtectedRoute><Category /></ProtectedRoute>} />
              <Route path="/users" element={<ProtectedRoute><User /></ProtectedRoute>} />
            </>
          )}
        </Routes>
      </div>
    </Router>
  );
}

export default App;




