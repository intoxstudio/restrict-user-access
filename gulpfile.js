'use strict';
const gulp = require('gulp');
const less = require('gulp-less');
const uglify = require('gulp-uglify');
const rename = require("gulp-rename");
const zip = require("gulp-zip");

gulp.task('less', function (done) {
	return gulp.src('css/style.less')
		.pipe(less({
			plugins: [
				new (require('less-plugin-autoprefix'))({ browsers: ['last 2 versions'] }),
				new (require('less-plugin-clean-css'))({advanced:true})
			]
		}))
		.pipe(gulp.dest('css'));
});

gulp.task('uglify', function () {
	return gulp.src(['js/*.js','!js/*.min.js'])
		.pipe(uglify({
			compress: {
				drop_console: true
			},
			mangle: {
				reserved: ['jQuery', 'WPCA','$']
			},
			output: {
				comments: 'some'
			},
			warnings: false
		}))
		.pipe(rename({extname: '.min.js'}))
		.pipe(gulp.dest('js'));
});

gulp.task('zip', function() {
	return gulp.src(['**','!build{,/**}','!**/node_modules{,/**}'],{base:'../'})
		.pipe(zip('restrict-user-access.zip'))
		.pipe(gulp.dest('build'));
});

gulp.task('watch', function() {
	gulp.watch('css/style.less', gulp.parallel('less'));
	gulp.watch(['js/*.js','!js/*.min.js'], gulp.parallel('uglify'));
});

gulp.task('build', gulp.parallel('less','uglify'));

gulp.task('deploy', gulp.series('build','zip'));

gulp.task('default', gulp.parallel('build'));

