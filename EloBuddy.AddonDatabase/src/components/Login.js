import React from 'react';
import { TextField, Card, CardTitle, RaisedButton } from 'material-ui';
import BaseComponent from './BaseComponent';

export default class Login extends BaseComponent {
  processForm(event) {
    if (event){
      event.preventDefault();
    }
    var username = this.refs.username.getValue();
    var password = this.refs.password.getValue();
  }
  render() {
    return (
      <Card className="Login">
        <form action="/" onSubmit={this.processForm.bind(this)}>
          <CardTitle title="Login" />
          <div>
            <TextField
              floatingLabelText="Username"
              ref="username"
            />
          </div>
          <div>
            <TextField
              floatingLabelText="Password"
              type="password"
              ref="password"
            />
          </div>
          <div>
            <RaisedButton type="submit" label="Done" primary={true} />
          </div>
        </form>
      </Card>
    );
  }
}
