// From http://knpuniversity.com/screencast/gulp/version-cache-busting

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')();

var config = {
    assetsDir: 'app/Resources/assets',
    sassPattern: 'sass/**/*.scss',
    production: !!plugins.util.env.production,
    sourceMaps: false,
    renameCss: false,
    renameJs: false,
    cleanCss: true,
    uglifyJs: false,
    dropConsole: false,
    vendorDir: 'vendor',
    bowerDir: 'vendor/bower-asset',
    npmDir: 'vendor/npm-asset'
};
var app = {};

app.addStyle = function(paths, outputFilename) {
    gulp.src(paths)
        .pipe(plugins.plumber())
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.init()))
        .pipe(plugins.sass().on('error', plugins.sass.logError))
        .pipe(plugins.concat('css/'+outputFilename))
        .pipe(config.cleanCss ? plugins.cleanCss({compatibility: ''}) : plugins.util.noop())
        .pipe(config.renameCss ? plugins.rename({extname: '.min.css'}) : plugins.util.noop())
        .pipe(gulp.dest('web'))
        .pipe(plugins.rev())
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.write('.')))
        .pipe(gulp.dest('web'))
        // write the rev-manifest.json file for gulp-rev
        .pipe(plugins.rev.manifest('app/Resources/assets/rev-manifest.json', {
            merge: true
        }))
        .pipe(gulp.dest('.'));
};

app.addScript = function(paths, outputFilename) {
    gulp.src(paths)
        .pipe(plugins.plumber())
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.init()))
        .pipe(plugins.concat('js/'+outputFilename))
        .pipe(config.uglifyJs ? plugins.uglify({compress: {drop_console:config.dropConsole,hoist_vars:false,hoist_funs:false,passes:2}}) : plugins.util.noop())
        .pipe(config.renameJs ? plugins.rename({extname: '.min.js'}) : plugins.util.noop())
        .pipe(gulp.dest('web'))
        .pipe(plugins.rev())
        .pipe(plugins.if(config.sourceMaps, plugins.sourcemaps.write('.')))
        .pipe(gulp.dest('web'))
        // write the rev-manifest.json file for gulp-rev
        .pipe(plugins.rev.manifest('app/Resources/assets/rev-manifest.json', {
            merge: true
        }))
        .pipe(gulp.dest('.'));
};

app.copy = function(srcFiles, outputDir) {
    gulp.src(srcFiles)
        .pipe(gulp.dest(outputDir));
};



gulp.task('styles', function() {
    app.addStyle([
        config.assetsDir+'/sass/application.scss',
        // - normalize and h5bp resets are part of custom _bootstrap.scss
        //config.bowerDir+'/normalize.css/normalize.css',
        //config.bowerDir+'/html5-boilerplate/dist/css/main.css',
        //  - bootstrap loaded via application.scss above
        //config.bowerDir+'/bootstrap-sass/assets/stylesheets/_bootstrap.scss',
        //config.bowerDir+'/bootstrap/dist/css/bootstrap.css',
        config.vendorDir+'/fortawesome/font-awesome/css/font-awesome.css'
        // -- all below now loaded from application.scss
        //config.bowerDir+'/fancytree/dist/skin-lion/ui.fancytree.css',
        //config.assetsDir+'/sass/layout.scss',
        //config.assetsDir+'/sass/styles.scss',
        //config.assetsDir+'/sass/overrides.scss',
        //config.vendorDir+'/mervick/material-design-icons/scss/material-icons.scss'
    ], 'main.css');
    app.addStyle([
        config.bowerDir+'/jquery-comments/css/jquery-comments.css',
        config.assetsDir+'/sass/comments.scss'
    ], 'comments.css');
});

gulp.task('scripts', function() {
    app.addScript([
        config.bowerDir+'/html5-boilerplate/dist/js/vendor/modernizr-2.8.3.min.js',
    ], 'modernizr.js');
    app.addScript([
        config.bowerDir+'/jquery-comments/js/jquery-comments.min.js',
        config.assetsDir+'/js/lsdoc/comments.js',
    ], 'comments.js');
    app.addScript([
        config.bowerDir+'/html5-boilerplate/dist/js/plugins.js',
        config.bowerDir+'/jquery/dist/jquery.js',
        config.bowerDir+'/jquery-ui/jquery-ui.js',
        config.bowerDir+'/bootstrap-sass/assets/javascripts/bootstrap.js',
        config.bowerDir+'/fancytree/dist/jquery.fancytree-all.js',
        config.bowerDir+'/datatables.net/js/jquery.dataTables.js',
        config.bowerDir+'/datatables.net-bs/js/dataTables.bootstrap.js',
        config.bowerDir+'/datatables.net-fixedheader/js/dataTables.fixedHeader.js',
        config.bowerDir+'/datatables.net-scroller/js/dataTables.scroller.js',
        config.bowerDir+'/datatables.net-select/js/dataTables.select.js',
        config.bowerDir+'/ui-contextmenu/jquery.ui-contextmenu.js',
        config.bowerDir+'/bootstrap-multiselect/dist/js/bootstrap-multiselect.js',
        config.bowerDir+'/select2/dist/js/select2.full.js',
        config.vendorDir+'/tetranz/select2entity-bundle/Tetranz/Select2EntityBundle/Resources/public/js/select2entity.js',
        config.bowerDir+'/twbs-pagination/jquery.twbsPagination.js',
        config.npmDir+'/markdown-it/dist/markdown-it.js',
        config.assetsDir+'/js/application.js',
        config.assetsDir+'/js/lsdoc/index.js',
        config.assetsDir+'/js/cftree/view-documentclass.js',
        config.assetsDir+'/js/cftree/view-trees.js',
        config.assetsDir+'/js/cftree/view-edit.js',
        config.assetsDir+'/js/cftree/view-modes.js',
        config.assetsDir+'/js/cftree/viewx.js',
    ], 'site.js');
});

gulp.task('fonts', function() {
    app.copy([
            config.vendorDir+'/fortawesome/font-awesome/fonts/*',
            config.bowerDir+'/bootstrap-sass/assets/fonts/bootstrap/glyphicons-halflings*',
            config.vendorDir+'/mervick/material-design-icons/fonts/MaterialIcons*'
        ],
        'web/fonts'
    );
});

gulp.task('images', function() {
    app.copy(
        // config.vendorDir+'/fortawesome/font-awesome/fonts/*',
        config.bowerDir+'/fancytree/dist/skin-lion/icons.gif',
        'web/img/fancytree/'
    );
});

gulp.task('watch', function() {
    gulp.watch(config.assetsDir+'/'+config.sassPattern, ['styles']);
    gulp.watch(config.assetsDir+'/js/**/*.js', ['scripts']);
});

//gulp.task('default', ['styles', 'scripts', 'fonts', 'watch']);
gulp.task('default', ['styles', 'scripts', 'fonts', 'images']);

