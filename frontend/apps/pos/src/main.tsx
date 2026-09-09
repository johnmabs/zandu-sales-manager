import { NotificationProvider } from "@zandu/notifications/react";
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";

import { App } from "./App";
import "./styles.css";
import "@zandu/notifications/styles.css";

const rootElement = document.getElementById("root");

if (rootElement === null) {
  throw new Error("The POS root element is missing.");
}

createRoot(rootElement).render(
  <StrictMode>
    <NotificationProvider>
      <App />
    </NotificationProvider>
  </StrictMode>,
);
