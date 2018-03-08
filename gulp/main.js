var gulp = require("gulp"),
    del = require("del"),
    clear = require("clear");

module.exports = function() {
    // Include gulp
    var gulp = require('gulp'); 

    // Include Our Plugins
    var jshint = require('gulp-jshint'),
        stylish = require('jshint-stylish'),
        sass = require('gulp-sass'),
        concat = require('gulp-concat'),
        uglify = require('gulp-uglify'),
        rename = require('gulp-rename'),
        mocha = require('gulp-mocha'),
        path  = require('path');

    var spawn = require('child_process').spawn,
        node;

    var src = 'src/wp-content/themes/*/';

    var jsHintOptions = {
        unused: true, // Highlight variables that are declared but not used
        curly: true, // Show statements that aren't followed by curly brackets
        undef: true, // Warn if variables are used before they are declared
        jquery: true, // Allow variables set by jQuery e.g. $
        browser: true, // Allow variables set by the browser e.g. window, document
        globals: {
            "Handlebars": true,
            "alert": true,
            "console": true
        }
    }

    // Move all static files
    gulp.task('move', function() {
        gulp.src(['./src/**/*', '!src/**/*.js', '!src/**/*.css', '!src/**/*.scss'])
            .pipe(gulp.dest('dist'));
    });

    // Lint Task
    gulp.task('lint', function() {
        return gulp.src([src+'js/*.js', '!'+src+'js/*.min.js'])
            .pipe(jshint(jsHintOptions))
            .pipe(jshint.reporter(stylish));
    });

    function lint(file) {
        return gulp.src(file)
            .pipe(jshint(jsHintOptions))
            .pipe(jshint.reporter(stylish));
    }

    // Move CSS
    gulp.task('css', function() {
        return gulp.src('src/**/*.css')
            .pipe(gulp.dest('dist'));
    });

    // Compile our Sass
    gulp.task('sass', function() {
        var s = sass();
        s.on('error', function(e) { console.log("Error processing SASS", e); this.emit('end'); });

        return gulp.src(['src/**/*.scss', '!src/**/_*.scss'])
            .pipe(s)
            .pipe(gulp.dest('dist'));
    });

    // Concatenate & Minify JS - unless filename starts with an underscore
    gulp.task('scripts', function() {
        var s = uglify();
        s.on('error', function() { console.log("Error processing JS"); this.emit('end'); });

        return gulp.src(['src/**/*.js', '!src/**/*.min.js'])
          //  .pipe(s)
            .pipe(gulp.dest('dist'));
    });

    // Move static scripts
    gulp.task('scriptsStatic', function() {
        return gulp.src(['src/**/*.min.js'])
            .pipe(gulp.dest('dist'));
    });

    // Watch Files For Changes
    gulp.task('watch', function() {
        gulp.watch(['src/**/*']).on('change', function (event) {
            clear();
        });

        gulp.watch(['src/**/*.js']).on('change', function (event) {
            lint(event.path);
        });

        gulp.watch('src/**/*.js', ['scripts', 'scriptsStatic']);
        gulp.watch('src/**/*.scss', ['sass']);
        gulp.watch('src/**/*.css', ['css']);

        // Delete files
        gulp.watch(['src/**/*']).on('change', function (event) {
            if (event.type === 'deleted') {
                var filePathFromSrc = path.relative(path.resolve('src/'), event.path);
                var destFilePath = path.resolve('dist/', filePathFromSrc);
                del.sync(destFilePath);
            } else {
                gulp.start('move');
            }
        });
    });
}