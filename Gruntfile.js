module.exports = function( grunt ) {
	require( 'matchdep' ).filterDev( ['grunt-*', '!grunt-legacy-util'] ).forEach( grunt.loadNpmTasks );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		checktextdomain: {
			options: {
				correct_domain: false,
				text_domain: ['profil-de-groupes'],
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'_n:1,2,4d',
					'_ex:1,2c,3d',
					'_nx:1,2,4c,5d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: ['**/*.php', '!**/node_modules/**', '!**/vendor/**'],
				expand: true
			}
		},
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: ['node_modules/.*', 'vendor/.*'],
					mainFile: 'profil-de-groupes.php',
					potFilename: 'profil-de-groupes.pot',
					processPot: function( pot ) {
						pot.headers['last-translator']      = 'imath <contact@imathi.eu>';
						pot.headers['language-team']        = 'FRENCH <contact@imathi.eu>';
						pot.headers['report-msgid-bugs-to'] = 'https://github.com/imath/profil-de-groupes/issues';
						return pot;
					},
					type: 'wp-plugin'
				}
			}
		},
		exec: {
			test_php: {
				command: 'composer test',
				stdout: true,
				stderr: true
			},
			test_php_multisite: {
				command: 'composer test_multisite',
				stdout: true,
				stderr: true
			}
		},
		'git-archive': {
			archive: {
				options: {
					'format'  : 'zip',
					'output'  : '<%= pkg.name %>.zip',
					'tree-ish': 'HEAD@{0}'
				}
			}
		}
	} );

	/**
	 * Register tasks.
	 */
	grunt.registerTask( 'phpunit', ['exec:test_php', 'exec:test_php_multisite'] );

	grunt.registerTask( 'compress', ['git-archive'] );

	grunt.registerTask( 'release', ['checktextdomain', 'makepot', 'phpunit'] );

	// Default task.
	grunt.registerTask( 'default', ['checktextdomain'] );
};
