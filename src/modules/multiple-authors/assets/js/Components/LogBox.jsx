class LogBox extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        return <div class="ppma_maintenance_log" role="status" aria-live="polite" aria-atomic="true" readOnly={true}>{this.props.log}</div>;
    }
}

export default LogBox;
