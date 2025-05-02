import React from "react";
import { Link, useNavigate } from "react-router-dom";
import { Menu, MenuButton, MenuItems, MenuItem } from "@headlessui/react";
import { ChevronDownIcon, ArrowLeftOnRectangleIcon, HomeIcon } from "@heroicons/react/24/outline";

function Navbar() {
  const navigate = useNavigate();
  const token = localStorage.getItem("token");
  const user = JSON.parse(localStorage.getItem("user"));
  const role = user?.role || "Guest";

  const handleLogout = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("user");
    navigate("/login");
  };

  return (
    <nav className="bg-white border-b border-gray-200 shadow-sm dark:bg-gray-900 dark:border-gray-700">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          <div className="flex items-center gap-6">
            <Link to="/" className="flex items-center gap-2 text-gray-800 dark:text-white font-bold text-lg hover:text-blue-600 dark:hover:text-blue-400">
              <HomeIcon className="w-5 h-5" /> Home
            </Link>

            {token && (role === "User" || role === "Admin") && (
              <>
                <Link to="/movies" className="text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Movies</Link>
                <Link to="/series" className="text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Serie TV</Link>
                <Link to="/episodes" className="text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Episodi</Link>
              </>
            )}

            {token && role === "Admin" && (
              <Menu as="div" className="relative inline-block text-left">
                <MenuButton className="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">
                  Admin <ChevronDownIcon className="w-4 h-4" />
                </MenuButton>
                <MenuItems className="absolute mt-2 w-40 bg-white dark:bg-gray-800 text-black dark:text-white rounded shadow-lg z-50">
                  <MenuItem>
                    {({ active }) => (
                      <Link
                        to="/categories"
                        className={`block px-4 py-2 text-sm ${active ? "bg-gray-100 dark:bg-gray-700" : ""}`}
                      >
                        Categorie
                      </Link>
                    )}
                  </MenuItem>
                  <MenuItem>
                    {({ active }) => (
                      <Link
                        to="/users"
                        className={`block px-4 py-2 text-sm ${active ? "bg-gray-100 dark:bg-gray-700" : ""}`}
                      >
                        Utenti
                      </Link>
                    )}
                  </MenuItem>
                </MenuItems>
              </Menu>
            )}
          </div>

          <div className="flex items-center gap-4">
            {token && (
              <span className="text-sm text-gray-700 dark:text-gray-200">
                {user?.name && `Ciao, ${user.name}`}
              </span>
            )}

            {token ? (
              <button
                onClick={handleLogout}
                className="flex items-center gap-1 text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md"
              >
                <ArrowLeftOnRectangleIcon className="w-4 h-4" /> Logout
              </button>
            ) : (
              <>
                <Link to="/login" className="text-sm text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Login</Link>
                <Link to="/register" className="text-sm text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400">Register</Link>
              </>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
}

export default Navbar;





