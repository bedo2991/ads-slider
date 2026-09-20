import './bootstrap';

import * as bootstrap from 'bootstrap'
window.bootstrap = bootstrap; // used for enabling tooltips

import $ from "jquery";
window.$ = window.jQuery = $;

import.meta.glob([
    '../img/**',
    '../assets/fonts/**',
]);