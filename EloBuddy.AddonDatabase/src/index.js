// src/index.js @flow
import React from 'react';
import ReactDOM from 'react-dom';

import Routes from './routes';

import './index.css';

import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap/dist/css/bootstrap-theme.css';

ReactDOM.render(
  <Routes />,
  document.getElementById('root')
);
