// src/components/App/index.js
import React, { Component } from 'react';
import logo from './logo.svg';
import './style.css';
import Fingerprint2 from 'fingerprintjs2';

class App extends Component {
  componentDidMount() {
    new Fingerprint2().get(function(result, components){
      console.log(result); //a hash, representing your device fingerprint
      console.log(components); // an array of FP components
    });
  }
  render() {
    return (
      <div className="App">
        <div className="App-header">
          <img src={logo} className="App-logo" alt="logo" />
          <h2>Welcome to React</h2>
        </div>
        <p className="App-intro">
          To get started, edit <code>src/App.js</code> and save to reload.
        </p>
      </div>
    );
  }
}

export default App;
