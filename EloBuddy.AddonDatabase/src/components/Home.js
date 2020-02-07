import React from 'react';
import { AppBar, Tabs, Tab, IconButton } from 'material-ui';
import BaseComponent from './BaseComponent';
import SwipeableViews from 'react-swipeable-views';
import { Link } from 'react-router';
import SearchIcon from 'material-ui/svg-icons/action/search';
import CloseIcon from 'material-ui/svg-icons/navigation/close';

const styles = {
  tabWidth: 450,
  appBar: {
    flexWrap: 'wrap'
  },
  tabs: {
    width: '100%'
  }
};

export default class Home extends BaseComponent {
  constructor(props) {
    super(props);
    this.state = { tabIndex: 1, searching: false };
  }
  onLeftIconButtonTouchTap() {
    console.log("Pressing");
    this.setState({ searching: !this.state.searching });
  }
  onTabIndexChange(value) {
    this.setState({ tabIndex: value });
  }
  render() {
    var tabs = (
        <Tabs
          tabItemContainerStyle={{ width: styles.tabWidth }}
          onChange={this.onTabIndexChange.bind(this)}
          value={this.state.tabIndex}
          style={styles.tabs}
          >
          <Tab label='Featured' value={0}/>
          <Tab label='Champions' value={1}/>
          <Tab label='Utilities' value={2}/>
          <Tab label='Libraries' value={3}/>
        </Tabs>
    );
    return (
      <div className="AppBarLayout">
      <AppBar
        onLeftIconButtonTouchTap={this.onLeftIconButtonTouchTap.bind(this)}
        style={styles.appBar}
        iconElementLeft={<IconButton>{this.state.searching ? <CloseIcon/> : <SearchIcon/>}</IconButton> }
        iconElementRight={tabs}
      >
      </AppBar>
      <SwipeableViews
        index={this.state.tabIndex}
        onChangeIndex={this.onTabIndexChange.bind(this)}
        >
        <div>
          <Link to="/register">Register</Link>
        </div>
        <div>
          slide n°2
        </div>
        <div>
          slide n°3
        </div>
        <div>
          slide n°4
        </div>
      </SwipeableViews>
      {this.props.children}
      </div>
    );
  }
}
