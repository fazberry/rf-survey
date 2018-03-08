// Include gulp
var gulp = require('gulp');

require('./gulp/main.js')();

// Default Task
gulp.task('default', ['move', 'lint', 'css', 'sass', 'scripts', 'scriptsStatic', 'watch']);