module.exports = function(grunt) {
	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		sass: {
			dev: {
				options: {
					style: 'expanded',
					sourcemap: 'none'
				},

				files: {
					'assets/css/public-dev.css': 'assets/css/public.scss',
					'assets/css/admin-dev.css': 'assets/css/admin.scss'
				}
			},
			dist: {
				options: {
					style: 'compressed',
					sourcemap: 'none'
				},

				files: {
					'assets/css/public.css': 'assets/css/public-dev.css',
					'assets/css/admin.css': 'assets/css/admin-dev.css'
				}
			}
		},

		jshint: [
			"assets/js/public.js",
			"assets/js/admin.js"
		],

		uglify: {
			dist: {
				files: {
					'assets/js/public.min.js': ['assets/js/public.js'],
					'assets/js/admin.min.js': ['assets/js/admin.js']
				}
			}
		},

		watch: {
			sass: {
				files: ['assets/css/*.scss'],
				tasks: ['sass']
			},
			scripts: {
				files: ['assets/js/*.js', '!assets/js/*.min.js'],
				tasks: ['jshint', 'uglify']
			},
			reload: {
				files: ['assets/css/*.css', 'assets/js/*.js'],
				options: { livereload: true }
			},
			markdown: {
				files: ['readme.txt'],
				tasks: ['wp_readme_to_markdown']
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
			zip: 'git-archive-all ../gravityview-ratings-reviews.zip'
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'readme.md': 'readme.txt'
				},
			},
		},

		// Build translations without POEdit
		makepot: {
			target: {
				options: {
					mainFile: 'ratings-reviews.php',
					type: 'wp-plugin',
					domainPath: '/languages',
					updateTimestamp: false,
					exclude: ['node_modules/.*', 'assets/.*', 'vendor/.*' ],
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
								'GravityView - Ratings & Reviews',
								'Add support for Ratings and Reviews of entries in GravityView',
								'https://gravityview.co/extensions/ratings-reviews/',
								'Katz Web Services, Inc.',
								'https://gravityview.co'
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
				textdomain: 'gravityview-ratings-reviews',    // Project text domain.
				updateDomains: [ 'gravityview', 'gravity-view', 'gravityforms', 'edd_sl', 'edd' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [
						'*.php',
						'**/*.php',
						'!.tx/**',
						'!.idea/**',
						'!node_modules/**',
						'!tests/**',
					]
				}
			}
		}
	});

	// Load the plugin(s).
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks("grunt-contrib-jshint");
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
	grunt.loadNpmTasks('grunt-potomo');
	grunt.loadNpmTasks('grunt-exec');

	// Task(s).
	grunt.registerTask('default', [ 'watch' ]);

	// Translation stuff
	grunt.registerTask( 'translate', [ 'exec:transifex', 'potomo', 'addtextdomain', 'makepot' ] );
};
