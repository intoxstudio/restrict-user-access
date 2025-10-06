'use strict';
const gulp = require('gulp');
const less = require('gulp-less');
const uglify = require('gulp-uglify');
const cleanCSS = require('gulp-clean-css');
const rename = require("gulp-rename");
const del = require('del');
const pkg = require('./package.json');

gulp.task('less', function (done) {
	return gulp.src('assets/css/style.less')
		.pipe(less({
			plugins: [
				new (require('less-plugin-autoprefix'))({ browsers: ["> 1%"] })
			]
		}))
		.pipe(cleanCSS({
			compatibility: '*',
			//format:'beautify',
			level: {
				1: {
					specialComments:true
				},
				2: {
				}
			}
		}))
		.pipe(gulp.dest('assets/css'));
});

gulp.task('uglify', function () {
	return gulp.src(['assets/js/*.js','!assets/js/*.min.js'])
		.pipe(uglify({
			compress: {
				drop_console: true
			},
			mangle: {
				reserved: ['jQuery', 'WPCA', 'RUA','$']
			},
			output: {
				comments: 'some'
			},
			warnings: false
		}))
		.pipe(rename({extname: '.min.js'}))
		.pipe(gulp.dest('assets/js'));
});

gulp.task('clean:svn', function () {
	return del(['C:/local/svn/'+pkg.name+'/trunk/**/*'],{force:true});
});

gulp.task('svn', function() {
	return gulp.src([
		'./**',
		'!fs-config.json',
		'!docs{,/**}',
		'!build{,/**}',
		'!vendor{,/**}',
		'!**/composer.*',
		'!**/package*.json',
		'!**/pnpm-lock.yaml',
		'!**/phpdoc.xml',
		'!lib/freemius/assets/scripts{,/**}',
		'!lib/freemius/gulptasks{,/**}',
		'!lib/freemius/patches{,/**}',
		'!**/CONTRIBUTING.md',
		'!**/*.{less,scss,po,pot,js}',
		'!**/{scss,less}{,/**}',
		'{lib/freemius/assets/js/*,**/*.min}.js',
		'!**/node_modules{,/**}',
        'vendor/autoload.php',
        'vendor/composer/{autoload_real.php,autoload_static.php,ClassLoader.php,LICENSE}'
	], {base : './'})
	.pipe(gulp.dest('C:/local/svn/'+pkg.name+'/trunk'));
});

gulp.task('watch', function() {
	gulp.watch('assets/css/style.less', gulp.parallel('less'));
	gulp.watch(['assets/js/*.js','!assets/js/*.min.js'], gulp.parallel('uglify'));
});

gulp.task('build', gulp.parallel('less','uglify'));

gulp.task('deploy', gulp.series('clean:svn','svn'));

gulp.task('default', gulp.parallel('build'));
