import React, { Component } from 'react';
import { blue500, blue700, pinkA200, green200, grey100, grey300, grey400, grey500, white, darkBlack, fullBlack } from 'material-ui/styles/colors';
import baseTheme from 'material-ui/styles/baseThemes/lightBaseTheme'
import getMuiTheme from 'material-ui/styles/getMuiTheme'
import injectTapEventPlugin from 'react-tap-event-plugin';
injectTapEventPlugin();

const muiTheme = getMuiTheme({
  palette: {
      primary1Color: '#125688',
      primary2Color: '#125688',
      primary3Color: '#125688',
      accent1Color: '#c8e8ff',
      accent2Color: '#c8e8ff',
      accent3Color: '#c8e8ff',
      pickerHeaderColor: '#125688',
    }
});
export default class BaseComponent extends Component {
  getChildContext() {
    return { muiTheme: muiTheme };
  }
}
BaseComponent.childContextTypes = {
  muiTheme: React.PropTypes.object.isRequired
}
