"use client";

import { useState } from "react";
import api from "@/lib/api/api";

import styles from "./page.module.scss";

export default () => {
    const [login, setLogin] = useState(null);
    const [password, setPassword] = useState(null);

    const handleSubmit = () => {
        api().user().login(login, password);
    };

    return (
        <form className={styles.login}>
            <input
                type="text"
                placeholder="Nom d'utilisateur"
                onChange={(e) => setLogin(e.target.value)}
            />
            <input
                type="password"
                placeholder="Mot de passe"
                onChange={(e) => setPassword(e.target.value)}
            />
            <button type="button" onClick={handleSubmit}>
                Se connecter
            </button>
        </form>
    );
};
