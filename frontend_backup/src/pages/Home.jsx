import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { FilmIcon, TvIcon, VideoCameraIcon } from "@heroicons/react/24/solid";
import { motion } from "framer-motion";
import api from "../api";

const Home = () => {
  const [movies, setMovies] = useState([]);
  const [series, setSeries] = useState([]);
  const [episodes, setEpisodes] = useState([]);

  const user = JSON.parse(localStorage.getItem("user"));
  const role = user?.role || "Guest";

  useEffect(() => {
    if (role === "Guest") return;

    const fetchData = async () => {
      try {
        const token = localStorage.getItem("token");
        const config = { headers: { Authorization: `Bearer ${token}` } };

        const [movieRes, seriesRes, episodeRes] = await Promise.all([
          api.get("/movies", config),
          api.get("/series", config),
          api.get("/episodes", config),
        ]);

        setMovies(movieRes.data.slice(0, 3));
        setSeries(seriesRes.data.slice(0, 3));
        setEpisodes(episodeRes.data.slice(0, 3));
      } catch (error) {
        console.error("Errore nel caricamento dei dati", error);
      }
    };

    fetchData();
  }, [role]);

  return (
    <div className="custom-container">
      <div className="text-center mb-12">
        <h1 className="text-4xl md:text-5xl font-extrabold text-black mb-4">
          Benvenuto su PenguinTube 🎬
        </h1>
        <p className="text-lg text-gray-600">
          Gestisci e scopri Film, Serie TV ed Episodi con semplicità.
        </p>
      </div>

      {role === "Guest" ? (
        <p className="text-center text-lg text-black">
          Effettua il login come User o Admin per accedere ai contenuti.
        </p>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
          {[
            {
              to: "/movies",
              icon: <FilmIcon className="h-10 w-10 text-blue-400 mb-3" />,
              title: "Film",
              items: movies,
            },
            {
              to: "/serietv",
              icon: <TvIcon className="h-10 w-10 text-blue-400 mb-3" />,
              title: "Serie TV",
              items: series,
            },
            {
              to: "/episode",
              icon: <VideoCameraIcon className="h-10 w-10 text-blue-400 mb-3" />,
              title: "Episodi",
              items: episodes,
            },
          ].map((section, i) => (
            <motion.div
              key={section.title}
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              whileHover={{ scale: 1.05 }}
              viewport={{ once: true, amount: 0.4 }}
              transition={{
                delay: i * 0.2,
                duration: 0.5,
                type: "spring",
                stiffness: 120,
              }}
            >
              <Link
                to={section.to}
                className="bg-gray-800 hover:bg-gray-700 transition rounded-xl p-6 shadow text-center flex flex-col items-center"
              >
                {section.icon}
                <h2 className="text-xl font-semibold text-white">{section.title}</h2>
                <ul className="text-sm text-gray-300 mt-2">
                  {section.items.map((item) => (
                    <li key={item.id}>{item.title}</li>
                  ))}
                </ul>
              </Link>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
};

export default Home;







