// Configuración mínima para Axios (cliente HTTP)
import axios from 'axios';
window.axios = axios;

// Indicar que las peticiones son AJAX por defecto
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
