module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        clean: {
            prod: {
                src: ["build/production/**/*.*"]
            },
            prodbuild: {
                src: [
                    "build/production/templates.js",
                   // "build/production/kqdraft.css"
                ]
            }
        },
        compass: {
            dev: {
                options: {
                    app: 'stand_alone',
                    sassDir: 'src',
                    cssDir: 'build/development'
                }
            },
            prod: {
                options: {
                    app: 'stand_alone',
                    sassDir: 'src',
                    cssDir: 'build/production'                }
            }
        },
        concat: {
            options: {
                separator: '\n',
                banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' + '<%= grunt.template.today("yyyy-mm-dd") %> */\n',
            },
            prod: {
                src: [
                    'bower_components/jquery/dist/jquery.min.js',
                    'bower_components/jquery-ui/jquery-ui.min.js',
                    'bower_components/jqueryui-touch-punch/jquery.ui.touch-punch.min.js',
                    'bower_components/angular/angular.min.js',
                    'bower_components/bootstrap/js/bootstrap.min.js',
                    'bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js',
                    'bower_components/angular-ui-router/release/angular-ui-router.min.js',
                    'bower_components/angular-ui-sortable/sortable.min.js',
                    'bower_components/slick/slick.min.js',
                    'bower_components/angular-slick-carousel/dist/angular-slick.min.js',
                    'build/production/**/*.js'
                ],
                dest: 'build/production/kqdraft-<%= grunt.template.today("yyyy-mm-dd") %>.js'
            }
        },
        connect: {
            dev: {
                options: {
                    port: 9000,
                    base: ['', 'vendor', 'build/development'],
                    livereload: true
                }
            },
            prod: {
                options: {
                    port: 9001,
                    base: ['build/production'],
                    livereload: true
                }
            }
        },
        copy: {
            prodfonts: {
                cwd: 'bower_components/bootstrap/dist/fonts/',
                src: ['**'],
                dest: 'build/production/fonts/',
                expand: true
            }
        },
        csslint: {
            dev: {
                options: {
                    import: false
                },
                src: ['build/development/**/*.css']
            },
            prod: {
                options: {
                    import: false
                },
                src: ['build/production/**/*.css']
            }
        },
        cssmin: {
            prod: {
                files: {
                        'build/production/kqdraft-<%= grunt.template.today("yyyy-mm-dd") %>.css' : [
                        'bower_components/bootstrap/**/*.min.css',
                        'bower_components/angular-bootstrap/**/*.min.css',
                        'build/production/kqdraft.css'
                    ]
                },
                minify: {
                    expand: true,
                    cwd: 'build/production/',
                    src: ['*.css', '!*.min.css'],
                    dest: 'build/production/',
                    ext: '.css'
                }
            },
        },
        html2js: {
            options: {
                module: 'templates'
            },
            dev: {
                src: ['src/**/*.html'],
                dest: 'build/development/templates.js'
            },
            prod: {
                src: ['src/**/*.html'],
                dest: 'build/production/templates.js'
            }
        },
        htmlbuild: {
            dev: {
                src: 'src/index.html',
                dest: 'build/development',
                options: {
                    styles: {
                        bundle: [
                            'bower_components/bootstrap/**/*.css',
                            'bower_components/angular-bootstrap/**/*.css',
                            'bower_components/slick-carousel/slick/*.css',
                            'build/development/**/*.css'
                        ]
                    },
                    scripts: {
                        bundle: [
                            'bower_components/jquery/dist/jquery.js',
                            'bower_components/jquery-ui/jquery-ui.js',
                            'bower_components/jqueryui-touch-punch/jquery.ui.touch-punch.js',
                            'bower_components/angular/angular.js',
                            'bower_components/bootstrap/js/bootstrap.js',
                            'bower_components/angular-bootstrap/ui-bootstrap-tpls.js',
                            'bower_components/angular-ui-router/release/angular-ui-router.js',
                            'bower_components/angular-ui-sortable/sortable.js',
                            'bower_components/slick-carousel/slick/slick.js',
                            'bower_components/angular-slick-carousel/dist/angular-slick.js',
                            'build/development/templates.js',
                            'src/**/*.js'
                        ]
                    },
                }
            },
            prod: {
                src: 'src/index.html',
                dest: 'build/production',
                options: {
                    styles: {
                        bundle: [
                            'build/production/kqdraft-<%= grunt.template.today("yyyy-mm-dd") %>.css'
                        ]
                    },
                    scripts: {
                        bundle: [
                            'build/production/kqdraft-<%= grunt.template.today("yyyy-mm-dd") %>.js'
                        ]
                    },
                }
            }
        },
        jshint: {
            all: {
                src: ['Gruntfile.js', 'src/**/*.js'],
                options: {
                    camelcase:false,
                    curly:true,
                    eqeqeq:true,
                    immed:true,
                    latedef:true,
                    newcap:false,
                    noarg:true,
                    sub:true,
                    laxbreak: true,
                    boss:true,
                    eqnull:true,
                    globals: {
                        moment: true,
                        jQuery: true,
                        console: true,
                        module: true,
                        document: true
                    }
                }
            }

        },
        ngAnnotate: {
            options: {
                singleQuotes: true
            },
            prod: {
                files: {
                    '<%= concat.prod.dest %>': ['src/**/*.js', 'build/production/templates.js']
                }
            }
        },
        uglify: {
            options: {
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
                mangle: false,
                sourceMap: true
            },
            prod: {
                options: {
                    sourceMap: false
                },
                files: {
                    '<%= concat.prod.dest %>': ['<%= concat.prod.dest %>']
                }
            }
        },
        watch: {
            dev: {
                files: ['Gruntfile.js', 'src/**/*.js', 'src/**/*.scss', '<%=csslint.dev.src %>' ,'src/**/*.html'],
                tasks: ['jshint', 'compass:dev', 'html2js:dev', 'htmlbuild:dev'],
                options: {
                    livereload: true,
                    spawn: false
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-csslint');
    grunt.loadNpmTasks('grunt-html-build');
    grunt.loadNpmTasks('grunt-contrib-connect');
    grunt.loadNpmTasks('grunt-contrib-compass');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-ng-annotate');
    grunt.loadNpmTasks('grunt-html2js');
    grunt.loadNpmTasks('grunt-contrib-copy');

    // on watch events configure jshint to only run on changed file
    grunt.event.on('watch', function(action, filepath, target) {

        if (grunt.file.isMatch('**/*.js', filepath)) {

            grunt.config('jshint.all.src', filepath);

        }
        else {

            grunt.config('jshint.all.src', '!' + filepath);

        }

    });

    grunt.registerTask('dev:server',
        [
            'jshint',
            'compass:dev',
            'html2js:dev',
            'htmlbuild:dev',
            'connect:dev',
            'watch:dev'
        ]
    );

    grunt.registerTask('default',
        [
            'clean:prod',
            'jshint',
            'compass:prod',
            'cssmin:prod',
            'html2js:prod',
            'ngAnnotate:prod',
            'uglify:prod',
            'concat:prod',
            'htmlbuild:prod',
            'copy:prodfonts',
            'clean:prodbuild'
        ]
    );
};
