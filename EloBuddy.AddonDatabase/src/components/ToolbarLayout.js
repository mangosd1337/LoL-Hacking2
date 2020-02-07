import React from 'react';
import { Toolbar, MenuItem, ToolbarGroup, DropDownMenu, ToolbarTitle, IconMenu, IconButton, FontIcon, RaisedButton, ToolbarSeparator } from 'material-ui';
import NavigationExpandMoreIcon from 'material-ui/svg-icons/navigation/expand-more';
import BaseComponent from './BaseComponent';

export default class ToolbarLayout extends BaseComponent {
  constructor(props) {
      super(props);
      this.state = {
        value: 3,
      };
    }

  handleChange = (event, index, value) => this.setState({value});
  render() {
    return (
      <Toolbar>
        <ToolbarGroup firstChild={true}>
          <DropDownMenu value={this.state.value} onChange={this.handleChange.bind(this)}>
            <MenuItem value={1} primaryText="All Broadcasts" />
            <MenuItem value={2} primaryText="All Voice" />
            <MenuItem value={3} primaryText="All Text" />
            <MenuItem value={4} primaryText="Complete Voice" />
            <MenuItem value={5} primaryText="Complete Text" />
            <MenuItem value={6} primaryText="Active Voice" />
            <MenuItem value={7} primaryText="Active Text" />
          </DropDownMenu>
        </ToolbarGroup>
        <ToolbarGroup>
          <ToolbarTitle text="Options" />
          <FontIcon className="muidocs-icon-custom-sort" />
          <ToolbarSeparator />
          <RaisedButton label="Create Broadcast" primary={true} />
          <IconMenu
            iconButtonElement={
              <IconButton touch={true}>
                <NavigationExpandMoreIcon />
              </IconButton>
            }
          >
            <MenuItem primaryText="Download" />
            <MenuItem primaryText="More Info" />
          </IconMenu>
        </ToolbarGroup>
      </Toolbar>
    );
  }
}
