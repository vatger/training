import "./styles.css";
import "flowbite";
import htmx from "htmx.org";
import Alpine from "alpinejs";

//import "./tailwind-test.js";

window.Alpine = Alpine;
window.htmx = htmx;

Alpine.start();
console.log("Frontend initialized with Tailwind CSS v4");