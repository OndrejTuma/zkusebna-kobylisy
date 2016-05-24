var gulp = require('gulp');

var paths = {
	assetsDir: "public/assets/",
	distDir: "public/dist/",
	imagesAssets: "public/assets/images/",
	imagesDist: "public/dist/images/",
	jsAssets: "public/assets/js/",
	jsDist: "public/dist/js/",
	cssAssets: "public/assets/css/",
	cssDist: "public/dist/css/"
};

var jshint = require('gulp-jshint');
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var spritesmith = require('gulp.spritesmith');
var imagemin = require('gulp-imagemin');
var pngquant = require('imagemin-pngquant'); // $ npm i -D imagemin-pngquant
var csso = require('gulp-csso');
var cache = require('gulp-cached');
var gutil = require('gulp-util');


var browserSync = require('browser-sync').create();

// Lint Task
gulp.task('lint', function() {
	return gulp.src(paths.jsAssets + "*.js")
		.pipe(jshint())
		.pipe(jshint.reporter('default'));
});

// Image minification
gulp.task('imagemin', function() {
	return gulp.src([paths.imagesAssets + "**/*", "!" + paths.imagesAssets + "{sprites,sprites/**}"])
		.pipe(cache('imagining'))
		.pipe(imagemin({
			progressive: true,
			optimizationLevel: 3,
			use: [pngquant()]
		}))
		.pipe(gulp.dest(paths.imagesDist));
});

// Compile Our Sass
gulp.task('sass', function() {
	return gulp.src(paths.cssAssets + "site.scss")
		.pipe(sass())
		.on('error', sass.logError)
		.pipe(gulp.dest(paths.cssDist))
		.pipe(browserSync.stream());
});

// Sprite generator
gulp.task('sprites', function() {
	var spriteData = gulp.src(paths.imagesAssets + "sprites/*.png").pipe(spritesmith({
		imgName: 'sprite.png',
		cssName: '_sprite.scss',
		imgPath: '../images/sprite.png'
	}));

	spriteData.img.pipe(gulp.dest(paths.imagesDist)); // output path for the sprite
	spriteData.css.pipe(gulp.dest(paths.cssAssets + "plugins/")); // output path for the CSS

	return spriteData;
});

// Concatenate & Minify JS
gulp.task('scripts', function() {
	return gulp.src(paths.jsAssets + "*.js")
		.pipe(concat('all.js'))
		.pipe(gulp.dest(paths.jsDist))
		//.pipe(rename('all.min.js'))
		//.pipe(uglify().on('error', gutil.log))
		//.pipe(gulp.dest(paths.jsDist))
		.pipe(browserSync.stream());
});
// Build production scripts
gulp.task('scripts-build', function() {
	return gulp.src(paths.jsAssets + "*.js")
		.pipe(concat('all.min.js'))
		.pipe(uglify().on('error', gutil.log))
		.pipe(gulp.dest(paths.jsDist));
});

// Optimize css
gulp.task('csso', function () {
	return gulp.src(paths.cssDist + 'site.css')
		.pipe(rename('site.min.css'))
		.pipe(csso())
		.pipe(gulp.dest(paths.cssDist));
});

// Watch Files For Changes
gulp.task('watch', function() {

	// Browser syncing
	browserSync.init({
		proxy: "zkusebna-kobylisy.tt"
	});

	gulp.watch(paths.jsAssets + "**/*.js", ['scripts']);
	gulp.watch(paths.cssAssets + "**/*.scss", ['sass']);
	gulp.watch(paths.imagesAssets + "sprites/*.png", ['sprites']);
	gulp.watch([paths.imagesAssets + "**/*", "!" + paths.imagesAssets + "{sprites,sprites/**}"], ['imagemin']);

	gulp.watch("{*,app/pages/*}.{php,html}").on("change", browserSync.reload);
});


gulp.task('build', ['sass', 'csso', 'scripts-build', 'sprites', 'imagemin']);

// Default Task
gulp.task('default', ['sass', 'scripts', 'watch']);