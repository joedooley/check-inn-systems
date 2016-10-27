module.exports = function(grunt) {

	grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		sass: {
			options: {
				outputStyle: 'compressed'
			},
			dist: {
				files: [{
		          expand: true,
		          cwd: 'assets/css/source',
		          src: ['*.scss'],
		          dest: 'assets/css',
		          ext: '.css'
		      }]
			}
		},

		watch: {
			scripts: {
				files: ['assets/js/*.js','!assets/js/*.min.js'],
				tasks: ['uglify:main','jshint']
			},
			scss: {
				files: ['assets/css/source/*.scss'],
				tasks: ['sass:dist']
			}
		},

		jshint: [
			"assets/js/admin.js"
		],

		uglify: {
			options: {
				mangle: false
			},
			main: {
				files: [{
			        expand: true,
			        cwd: 'assets/js',
			        src: ['**/*.js','!**/*.min.js'],
			        dest: 'assets/js',
			        ext: '.min.js'
		        }]
			}
		},

		dirs: {
			lang: 'languages'
		},

		// Convert the .po files to .mo files
		potomo: {
			dist: {
				options: {
					poDel: false
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.lang %>',
					src: ['*.po'],
					dest: '<%= dirs.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		},

		// Pull in the latest translations
		exec: {
			transifex: 'tx pull -a',

			// Create a ZIP file
			zip: 'git-archive-all ../gravityview-importer.zip'
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'gravityview-importer.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: ['node_modules/.*', 'assets/.*', 'vendor/.*', 'tests/.*' ],
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					processPot: function( pot, options ) {
						pot.headers['language'] = 'en_US';
						pot.headers['language-team'] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers['last-translator'] = 'Katz Web Services, Inc. <support@katz.co>';
						pot.headers['report-msgid-bugs-to'] = 'https://gravityview.co/support/';


						var translation,
							excluded_meta = [
								'GravityView',
								'GravityView - Gravity Forms Import Entries',
								'Create directories based on a Gravity Forms form, insert them using a shortcode, and modify how they output.',
							    'https://gravityview.co',
								'Import entries into Gravity Forms',
								'1.1-beta',
								'https://gravityview.co/extensions/gravity-forms-entry-importer/',
								'Katz Web Services, Inc.',
								'http://www.katzwebservices.com'
							];

						for ( translation in pot.translations[''] ) {
							if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
								if ( excluded_meta.indexOf( pot.translations[''][ translation ].msgid ) >= 0 ) {
									console.log( 'Excluded meta: ' + pot.translations[''][ translation ].msgid );
									delete pot.translations[''][ translation ];
								}
							}
						}

						return pot;
					}
				}
			}
		},

		// Add textdomain to all strings, and modify existing textdomains in included packages.
		addtextdomain: {
			options: {
				textdomain: 'gravityview-importer',    // Project text domain.
				updateDomains: [ 'gravityview', 'gravityforms', 'gravityview-importer', 'edd_sl', 'gravityview-import', 'edd' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!node_modules/**',
						'!tests/**',
						'!vendor/**',
					]
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
	grunt.loadNpmTasks('grunt-potomo');
	grunt.loadNpmTasks('grunt-exec');
    grunt.loadNpmTasks('grunt-newer');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask( 'default', [ 'sass', 'uglify', 'translate', 'watch' ] );

	// Translation stuff
	grunt.registerTask( 'translate', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );

};