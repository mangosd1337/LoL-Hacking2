import React from 'react';
import { TextField, Card, CardTitle, CardText, RaisedButton } from 'material-ui';
import BaseComponent from './BaseComponent';
import AuthStore from "../stores/AuthStore";
import keycode from 'keycode';

export default class Register extends BaseComponent {
  static defaultProps = {
    user: AuthStore.getUser()
  };
  constructor(props) {
    super(props);
    this.state = { user: props.user };
  }
  componentWillMount() {
    AuthStore.init();
  }

  componentDidMount() {
    AuthStore.addChangeListener(this.onStoreChange);
  }

  componentWillUnmount() {
    AuthStore.removeChangeListener(this.onStoreChange);
  }
  onStoreChange = () => {
      this.setState({
        user: AuthStore.getUser(),
      });
  }
  onKeyDown(e) {
    switch(keycode(e)) {
      case 'enter':
          //this.processForm();
        break;
      default:
        break;
    }
  }
  processForm(event) {
    if (event){
      event.preventDefault();
    }
    var token = this.refs.token.getValue();
    var username = this.refs.username.getValue();
    var password = this.refs.password.getValue();
    var confirmPassword = this.refs.confirmPassword.getValue();
    if (password === confirmPassword && password.trim()) {

    }
  }
  render() {
    return (
      <Card className="Register">
        <form action="/" onSubmit={this.processForm.bind(this)}>
          <CardTitle title="Register" />
          <div className="field-line">
            <TextField
              floatingLabelText="Token"
              ref="token"
            />
          </div>
          <div className="field-line">
            <TextField
              floatingLabelText="Username"
              ref="username"
            />
          </div>
          <div className="field-line">
            <TextField
              floatingLabelText="Password"
              type="password"
              ref="password"
            />
          </div>
          <div className="field-line">
            <TextField
              floatingLabelText="Confirm Password"
              type="password"
              ref="confirmPassword"
              onKeyDown={this.onKeyDown.bind(this)}
            />
          </div>
          <div className="button-line">
            <RaisedButton type="submit" label="Done" primary={true} />
          </div>
        </form>
      </Card>
    );
  }
}
