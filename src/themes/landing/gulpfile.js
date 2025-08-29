const gulp = require('gulp');
const fileinclude = require('gulp-file-include');
const sass = require('gulp-sass')(require('node-sass'));
const sassGlob = require('gulp-sass-glob');
const formatHtml = require('gulp-format-html').default;
const rename = require("gulp-rename");
const imageResize = require('gulp-image-resize');
const through2 = require('through2');
const sourcemaps = require("gulp-sourcemaps");
const browserSync = require('browser-sync').create();
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const concat = require('gulp-concat'); // Added missing dependency
const uglify = require('gulp-uglify'); // Added missing dependency

let baseHref = '';
let blockPrefix = "landing";

// Get command line parameters after some specified parameter
function parameters() {
	return process.argv.filter(a => a[0] == '-').map(a => a.slice(1));
}

const touch = () => through2.obj(function (file, enc, cb) {
	if (file.stat) {
		file.stat.atime = file.stat.mtime = file.stat.ctime = new Date();
	}
	cb(null, file);
});

// File Include Task
async function fileincludeTask() {

	return gulp.src(['./src/*.html', './src/**/*.html', '!**/-*/**', '!**/_*/**', '!**/_includes/**'])
		.pipe(fileinclude({
			prefix: '@@',
			basepath: '@file',
			context: {}
		}))
		.pipe(formatHtml())
		.pipe(touch())
		.pipe(gulp.dest('./'))
		.pipe(browserSync.stream());
}

// Sass Compilation Task
async function sassTask() {
	return gulp.src(['./src/_includes/sass/*.scss', './src/_includes/sass/**/*.scss', './src/_includes/sass/**/**/*.scss'])
		.pipe(sassGlob())
		.pipe(sourcemaps.init())
		.pipe(sass().on('error', function(err) {
			console.error('Sass Compilation Error:', err.message);
			this.emit('end');
		}))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest('./css/'))
		.pipe(browserSync.stream());
}

// // Sass Compilation Task
// async function componentSassTask() {
// 	return gulp.src(['./scss/component.scss', '!**/_*/**'])
// 		.pipe(sassGlob())
// 		.pipe(sourcemaps.init())
// 		.pipe(sass().on('error', function(err) {
// 			console.error('Sass Compilation Error:', err.message);
// 			this.emit('end');
// 		}))
// 		.pipe(sourcemaps.write('./'))
// 		.pipe(gulp.dest('./css'))
// 		.pipe(browserSync.stream());
// }
// // Task: Bundle JS
// async function bundleJS() {
// 	return gulp
// 		.src('./src/js/**/*.js')
// 		.pipe(concat("bundle.js"))
// 		.pipe(uglify())
// 		.pipe(gulp.dest('./js'))
// 		.pipe(browserSync.stream());
// }

// const pluginsPath = "./plugins/";
// const publicPath = "./public/";

// // Task: Compile SCSS for plugins
// function compilePluginSass() {
// 	return gulp
// 		.src(`${pluginsPath}*/sass/**/*.scss`)
// 		.pipe(sourcemaps.init())
// 		.pipe(sassGlob())
// 		.pipe(sass({ outputStyle: "compressed" }).on("error", sass.logError))
// 		.pipe(sourcemaps.write('./'))
// 		.pipe(gulp.dest(`${publicPath}plugins/`))
// 		.pipe(browserSync.stream());
// }

// // Task: Bundle JS for plugins
// function bundlePluginJS() {
// 	return gulp
// 		.src(`${pluginsPath}*/js/**/*.js`)
// 		.pipe(concat("bundle.js"))
// 		.pipe(uglify())
// 		.pipe(gulp.dest(`${publicPath}plugins/`))
// 		.pipe(browserSync.stream());
// }

// Watch Task
function watchTask() {
	gulp.watch(['./src/*.html', './src/**/*.html','./src/_includes/*.html', './src/_includes/**/*.html', '!**/-*/**', '!**/_*/**'], gulp.series(fileincludeTask));
	gulp.watch(['./src/_includes/sass/*.scss', './src/_includes/sass/**/*.scss', './src/_includes/sass/**/**/*.scss'], gulp.series(sassTask));
	// gulp.watch(['./scss/component.scss', './scss/**/**/*.scss'], gulp.series(componentSassTask));
	// gulp.watch(['./src/js/**/*.js'], gulp.series(bundleJS));
}

// Screenshots Task
// async function screenshots(type = "sections", dirs = []) {
// 	let sectionsDir = path.resolve("./" + type);
// 	let screenshotDir = path.resolve("./screenshots/" + type + "/");
// 	let baseDir = path.resolve(".");
// 	let sections = [];

// 	if (!dirs.length) {
// 		dirs = fs.readdirSync(sectionsDir).map(fileName => {
// 			let filePath = `${sectionsDir}/${fileName}`;
// 			if (fs.statSync(filePath).isDirectory()) {
// 				fs.readdirSync(filePath).map(sectionFile => {
// 					sections.push(`${filePath}/${sectionFile}`);
// 				});
// 			}
// 		});
// 	}

// 	const browser = await puppeteer.launch({ headless: true });
// 	const page = await browser.newPage();
// 	await page.setViewport({ width: 1500, height: 800 });

// 	for (let section of sections) {
// 		let screenshot = section.replace(sectionsDir, screenshotDir).replace('.html', '.png');
// 		let sectionScreenshot = section.replace('.html', '-screenshot.html');

// 		console.log(`Capturing screenshot for '${section}'`);

// 		let content = fs.readFileSync(section, 'utf8');
// 		let html = `<html><head><base href="../../"><link href="/css/style.css" rel="stylesheet"></head><body>${content}</body></html>`;
// 		fs.writeFileSync(sectionScreenshot, html);

// 		await page.goto(`file://${sectionScreenshot}`, { waitUntil: "load" });
// 		const element = await page.$("body > *");
// 		await element.screenshot({ path: screenshot });

// 		gulp.src(screenshot)
// 			.pipe(imageResize({ width: 480, format: "jpeg", quality: 0 }))
// 			.pipe(rename({ suffix: "-thumb" }))
// 			.pipe(gulp.dest(path.dirname(screenshot)));
// 	}

// 	await browser.close();
// }

// Serve Task
// async function connectTask(done) {
// 	connect.server({
// 		port: 8008
// 	});
// }
function serveTask(done) {
	browserSync.init({ server: { baseDir: './' } });  // assuming './' is the correct baseDir, might need adjusting
	done();
}

// Define Exports
exports.fileinclude = fileincludeTask;
exports.sass = sassTask;
exports.watch = gulp.series(fileincludeTask, serveTask, watchTask, sassTask);
exports.connect = serveTask;
// exports.screenshots = gulp.series(serveTask, () => screenshots("sections"));
// exports['screenshots-blocks'] = gulp.series(serveTask, () => screenshots("blocks"));
exports.default = gulp.series(serveTask, fileincludeTask);
