const NODE_ENV = process.env.NODE_ENV || 'development';

let path = require('path');

module.exports = {
    mode: NODE_ENV,
    entry: {
        'Components/ProgressBar': './src/modules/multiple-authors/assets/js/Components/ProgressBar.jsx',
        'Components/Button': './src/modules/multiple-authors/assets/js/Components/Button.jsx',
        'Components/LogBox': './src/modules/multiple-authors/assets/js/Components/LogBox.jsx',
        'Components/DataMigrationBox': './src/modules/multiple-authors/assets/js/Components/DataMigrationBox.jsx',
        'coauthors-migration': './src/modules/multiple-authors/assets/js/coauthors-migration.jsx',
        'molongui-authorship-migration': './src/modules/multiple-authors/assets/js/molongui-authorship-migration.jsx',
        'sync-post-author': './src/modules/multiple-authors/assets/js/sync-post-author.jsx',
        'sync-author-slug': './src/modules/multiple-authors/assets/js/sync-author-slug.jsx',
        'byline-migration': './src/modules/byline-migration/assets/js/byline-migration.jsx',
        'bylines-migration': './src/modules/bylines-migration/assets/js/bylines-migration.jsx',
        'author-boxes-block': './src/modules/author-boxes/assets/js/author-boxes-block.jsx',
    },
    output: {
        path: path.join(__dirname, 'src/assets/js'),
        filename: '[name].min.js'
    },
    module: {
        rules: [
            {
                test: /.jsx$/,
                exclude: /node_modules/,
                loader: 'babel-loader'
            }
        ]
    }
};
