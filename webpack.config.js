const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const WatchTimePlugin = require('webpack-watch-time-plugin');
const path = require('path');

module.exports = (env, argv) => ({
  entry: {
    'input-script': './assets/src/input.js',
    'input-style': './assets/src/input.scss',
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname, 'assets/dist'),
  },
  resolve: {
    extensions: ['*', '.js'],
  },
  performance: {
    hints: false,
  },
  devtool: 'source-map',
  mode: 'development',
  module: {
    rules: [
      {
        test: /\.js$/,
        use: [
          {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env'],
            },
          },
        ],
      },
      {
        test: /\.(png|svg|jpg|jpeg|tiff|webp|gif|ico|woff|woff2|eot|ttf|otf|mp4|webm|wav|mp3|m4a|aac|oga)$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              context: 'src',
              name: '[path][name].[ext]?ver=[md5:hash:8]',
            },
          },
        ],
      },
      {
        test: /\.s?css$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              ident: 'postcss',
              plugins: [
                require('autoprefixer')({}),
              ],
              sourceMap: true,
            }
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true,
            },
          },
        ],
      },
    ],
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
      chunkFilename: '[id].css',
    }),
    new WatchTimePlugin(),
  ],
});
