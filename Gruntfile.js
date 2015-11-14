module.exports = function(grunt) {

	/**
	 * Load tasks
	 */
	require('load-grunt-tasks')(grunt);

	/**
	 * Configuration
	 */
	grunt.initConfig({

		/**
		 * Load parameters
		 */
		pkg: grunt.file.readJSON('package.json'),

		/**
		 * Compile css
		 */
		less: {
			development: {
				options: {
					paths: ["css"],
					cleancss: true,
				},
				files: {
					"css/style.css": "css/style.less"
				}
			}
		},

		watch: {
			css: {
				files: '**/*.less',
				tasks: ['less']
			}
		}
	});

	/**
	 * Register tasks
	 */
	grunt.registerTask('default', ['build']);
	grunt.registerTask('build', ['less']);

};