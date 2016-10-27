module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'gravityview-math',
				updateDomains: [ 'gravityview', 'gravity-view', 'gravityforms', 'edd_sl', 'edd' ]  // List of text domains to replace.
			},
			target: {
				files: {
					src: [ '*.php', '**/*.php', '!node_modules/**', '!php-tests/**', '!bin/**', '!vendor/**', '!tmp/**' ]
				}
			}
		},
		
		// Pull in the latest translations
		exec: {
			transifex: 'tx pull -a',

			// Create a ZIP file
			zip: 'git-archive-all ../gravityview-math.zip'
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'gravityview-math.php',
					potFilename: 'gravityview-math.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: false,
					exclude: [ 'node_modules/.*', 'php-tests/.*', 'bin/.*', 'vendor/.*', 'tmp/.*' ],
					processPot: function( pot, options ) {
						pot.headers['language'] = 'en_US';

						var translation,
							excluded_meta = [
								'Math by GravityView',
								'Perform calculations inside or outside GravityView using the <code>[gv_math]</code> shortcode. Requires PHP 5.4.',
								'https://gravityview.co/extensions/math/',
								'GravityView',
								'https://gravityview.co/'
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
					src: ['*.pot'],
					dest: '<%= dirs.lang %>',
					ext: '.mo',
					nonull: true
				}]
			}
		}

	} );

	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-potomo' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot', 'potomo', 'exec:transifex'] );

	grunt.registerTask( 'default', ['i18n']);

	grunt.util.linefeed = '\n';

};
