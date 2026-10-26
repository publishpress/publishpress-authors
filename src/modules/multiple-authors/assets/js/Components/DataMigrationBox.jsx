import ProgressBar from "./ProgressBar.jsx";
import LogBox from "./LogBox.jsx";
import Button from "./Button.jsx";

let {__} = wp.i18n;

class DataMigrationBox extends React.Component {
    constructor(props) {
        super(props);

        this.startMigration = this.startMigration.bind(this);
        this.resetState = this.resetState.bind(this);
        this.migrateChunkOfData = this.migrateChunkOfData.bind(this);
        this.getInitialData = this.getInitialData.bind(this);
        this.finishCoAuthorsMigration = this.finishCoAuthorsMigration.bind(this);

        this.state = {
            totalToMigrate: 0,
            totalMigrated: 0,
            inProgress: false,
            progress: 0,
            log: ''
        };
    }

    getInitialData(next) {
        var self = this;

        self.setState({
            'log': self.props.messageCollectingData
        });

        window.setTimeout(() => {
            jQuery.ajax({
                type: 'GET',
                dataType: 'json',
                url: ajaxurl,
                async: false,
                data: {
                    action: self.props.actionGetInitialData,
                    nonce: self.props.nonce,
                },
                success: function (response) {
                    self.setState({
                        totalToMigrate: response.total
                    });

                    next();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.setState({
                        progress: 0,
                        inProgress: false,
                        log: __('Error: ', 'publishpress-authors') + errorThrown + ' [' + textStatus + ']'
                    });
                }
            });
        }, 1000);
    }

    startMigration() {
        var self = this;

        self.resetState();

        self.setState(
            {
                progress: 1,
                inProgress: true,
                log: self.props.messageWait
            }
        );

        window.setTimeout(() => {
            self.getInitialData(() => {
                self.setState(
                    {
                        progress: 2,
                        log: self.props.messageStarting
                    }
                );

                self.migrateChunkOfData();
            });
        }, 1000);
    }

    migrateChunkOfData() {
        var self = this;

        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: ajaxurl,
            data: {
                action: self.props.actionMigrationStep,
                nonce: self.props.nonce,
                chunkSize: self.props.chunkSize
            },
            success: function (response) {
                let totalMigrated = self.state.totalMigrated + parseInt(response.totalMigrated);

                if (totalMigrated > self.state.totalToMigrate) {
                    totalMigrated = self.state.totalToMigrate;
                }

                let logMessage = self.props.messageProgress.replace('%d', totalMigrated).replace('%d', self.state.totalToMigrate);

                self.setState({
                    totalMigrated: totalMigrated,
                    progress: 2 + (Math.floor((98 / self.state.totalToMigrate) * totalMigrated)),
                    log: logMessage,
                });

                if (totalMigrated < self.state.totalToMigrate) {
                    self.migrateChunkOfData();
                } else {
                    self.finishCoAuthorsMigration(function () {
                        self.setState({
                            progress: 100,
                            log: self.props.messageDone.replace('%d', self.state.totalMigrated)
                        });

                        window.setTimeout(() => {
                            self.setState({
                                inProgress: false
                            });
                        }, 1000);
                    });
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                self.setState({
                    progress: 0,
                    inProgress: false,
                    log: __('Error: ', 'publishpress-authors') + errorThrown + ' [' + textStatus + ']'
                });
            }
        });
    }

    finishCoAuthorsMigration(onFinishCallBack) {
        var self = this;

        self.setState({
            progress: 99,
            'log': self.props.messageEndingProcess
        });

        window.setTimeout(() => {
            jQuery.ajax({
                type: 'GET',
                dataType: 'json',
                url: ajaxurl,
                async: false,
                data: {
                    action: self.props.actionFinishProcess,
                    nonce: self.props.nonce
                },
                success: function (response) {
                    onFinishCallBack();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.setState({
                        progress: 0,
                        inProgress: false,
                        log: __('Error: ', 'publishpress-authors') + errorThrown + ' [' + textStatus + ']'
                    });
                }
            });
        }, 1000);
    }

    resetState() {
        this.setState({
            totalToMigrate: 0,
            totalMigrated: 0,
            inProgress: false,
            progress: 0,
            log: ''
        });
    }

    render() {
        let progressBar = (this.state.inProgress) ? <ProgressBar value={this.state.progress}/> : '';
        let logPanel = <LogBox log={this.state.log}/>;

        return (
            <div>
                <Button
                    label={this.props.buttonLabel}
                    onClick={this.startMigration}
                    enabled={!this.state.inProgress}
                    className={"button-secondary ppma_maintenance_button"}/>
                {progressBar}
                {logPanel}
            </div>
        );
    }
}

export default DataMigrationBox;
