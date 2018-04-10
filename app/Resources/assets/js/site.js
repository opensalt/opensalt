const $ = require('jquery');
global.$ = global.jQuery = $;

const session = require('./session');
session.check();
