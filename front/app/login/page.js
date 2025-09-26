'use client';

import api from "@/lib/api/api";
import { useState } from "react";

export default () => {
	const [login, setLogin] = useState(null);
	const [password, setPassword] = useState(null);

	const handleSubmit = () => {
		api().user.login(login, password);
	};

	return <div>
		<input type="text" placeholder="Login" onChange={(e) => setLogin(e.target.value)} />
		<input type="password" placeholder="Password" onChange={(e) => setPassword(e.target.value)} />
		<button onClick={handleSubmit}>Login</button>
	</div>;
}
