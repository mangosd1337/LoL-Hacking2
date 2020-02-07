// src/routes.js
import React from 'react';
import { Router, Route, browserHistory, Link } from 'react-router';

import Home from './components/Home';
import Login from './components/Login';
import Register from './components/Register';
import NotFound from './components/NotFound';

const App = React.createClass({
  render() {
    return (
      <div>
        <h1>App</h1>
        <ul>
          <li><Link to="/about">About</Link></li>
          <li><Link to="/inbox">Inbox</Link></li>
        </ul>
        {this.props.children}
      </div>
    )
  }
})

const About = React.createClass({
  render() {
    return <h3>About</h3>
  }
})

const Inbox = React.createClass({
  render() {
    return (
      <div>
        <h2>Inbox</h2>
        {this.props.children || "Welcome to your Inbox"}
      </div>
    )
  }
})

const Message = React.createClass({
  render() {
    return <h3>Message {this.props.params.id}</h3>
  }
})
const Routes = () => (
  <Router history={browserHistory}>
    {/*
    <Route path="/" component={App}>
      <Route path="about" component={About} />
      <Route path="inbox" component={Inbox}>
        <Route path="messages/:id" component={Message} />
      </Route>
    </Route>
    */}
    <Route path="/" component={Home}>
      <Route path="register" component={Register}/>
      <Route path="login" component={Login}/>
    </Route>
    <Route path="*" component={NotFound}/>
  </Router>
);

export default Routes;
