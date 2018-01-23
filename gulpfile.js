'use strict';
const gulp = require('gulp');
const less = require('gulp-less');
const uglify = require('gulp-uglify');
const rename = require("gulp-rename");
const zip = require("gulp-zip");
const del = require('del');
const pkg = require('./package.json');

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
				reserved: ['jQuery', 'WPCA', 'RUA','$']
			},
			output: {
				comments: 'some'
			},
			warnings: false
		}))
		.pipe(rename({extname: '.min.js'}))
		.pipe(gulp.dest('js'));
});

gulp.task('clean:svn', function () {
	return del(['D:/svn/'+pkg.name+'/trunk/**/*'],{force:true});
});

gulp.task('svn', function() {
	return gulp.src(['./**','!build{,/**}','!**/node_modules{,/**}'])
	.pipe(gulp.dest('D:/svn/'+pkg.name+'/trunk'));
});

gulp.task('watch', function() {
	gulp.watch('css/style.less', gulp.parallel('less'));
	gulp.watch(['js/*.js','!js/*.min.js'], gulp.parallel('uglify'));
});

gulp.task('build', gulp.parallel('less','uglify'));

gulp.task('deploy', gulp.series('clean:svn','svn'));

gulp.task('default', gulp.parallel('build'));

