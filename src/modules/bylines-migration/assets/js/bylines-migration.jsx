class PPAuthorsBylinesMigrationBox extends React.Component {
    constructor(props) {
        super(props);

        this.renderDeactivatePluginOption = this.renderDeactivatePluginOption.bind(this);
        this.renderProgressBar = this.renderProgressBar.bind(this);
        this.renderLog = this.renderLog.bind(this);
        this.deactivateBylines = this.deactivateBylines.bind(this);
        this.startMigration = this.startMigration.bind(this);
        this.clickStart = this.clickStart.bind(this);
        this.reset = this.reset.bind(this);
        this.migrateChunkOfData = this.migrateChunkOfData.bind(this);
        this.getBylinesMigrationInitialData = this.getBylinesMigrationInitialData.bind(this);

        this.state = {
            totalToMigrate: 0,
            totalMigrated: 0,
            inProgress: false,
            chunkSize: this.props.chunkSize,
            progress: 0,
            log: '',
            showDeactivateOption: false,
            disablingBylines: false
        };
    };

    clickStart(e) {
        e.preventDefault();

        this.startMigration();
    }

    getBylinesMigrationInitialData(next) {
        var self = this;

        this.setState({
            'log': ppmaBylinesMigration.start_message
        });

        window.setTimeout(() => {
            jQuery.ajax({
                type: 'GET',
                dataType: 'json',
                url: ajaxurl,
                async: false,
                data: {
                    action: 'get_bylines_migration_data',
                    nonce: this.props.nonce
                },
                success: function (response) {
                    if (!response.success) {
                        self.setState({
                            progress: 0,
                            inProgress: false,
                            log: ppmaBylinesMigration.error_message + response.error,
                            showDeactivateOption: false
                        });

                        return;
                    }

                    self.setState({
                        totalToMigrate: response.total
                    });

                    next();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.setState({
                        progress: 0,
                        inProgress: false,
                        log: ppmaBylinesMigration.error_message + errorThrown + ' [' + textStatus + ']',
                        showDeactivateOption: false
                    });
                }
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
                action: 'migrate_bylines',
                nonce: this.props.nonce,
                chunkSize: this.state.chunkSize
            },
            success: function (response) {
                if (!response.success) {
                    self.setState({
                        progress: 0,
                        inProgress: false,
                        log: ppmaBylinesMigration.error_message + response.error,
                        showDeactivateOption: false
                    });

                    return;
                }

                let totalMigrated = self.state.totalMigrated + self.state.chunkSize;

                if (totalMigrated > self.state.totalToMigrate) {
                    totalMigrated = self.state.totalToMigrate;
                }

                self.setState({
                    totalMigrated: totalMigrated,
                    progress: 2 + (Math.floor((98 / self.state.totalToMigrate) * totalMigrated))
                });

                if (totalMigrated < self.state.totalToMigrate) {
                    self.migrateChunkOfData();
                } else {
                    let logMessage = ppmaBylinesMigration.completed_message;

                    self.setState({
                        progress: 100,
                        log: logMessage,
                        showDeactivateOption: true
                    });

                    window.setTimeout(() => {
                        self.setState({
                            inProgress: false
                        });
                    }, 1000);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                self.setState({
                    progress: 0,
                    inProgress: false,
                    log: ppmaBylinesMigration.error_message + errorThrown + ' [' + textStatus + ']',
                    showDeactivateOption: false
                });
            }
        });
    }

    startMigration() {
        var self = this;

        this.setState(
            {
                progress: 1,
                inProgress: true,
                log: ppmaBylinesMigration.wait_message,
                showDeactivateOption: false
            }
        );

        window.setTimeout(() => {
            self.getBylinesMigrationInitialData(() => {
                self.setState(
                    {
                        progress: 2,
                        log: ppmaBylinesMigration.progress_message
                    }
                );

                self.migrateChunkOfData();
            });
        }, 1000);
    }

    deactivateBylines() {
        var self = this;

        this.setState(
            {
                disablingBylines: true,
                log: ppmaBylinesMigration.deactivating_message
            }
        );

        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: ajaxurl,
            data: {
                action: 'deactivate_bylines',
                nonce: this.props.nonce
            },
            success: function (response) {
                self.setState({
                    disablingBylines: false,
                    log: ppmaBylinesMigration.deactivated_message,
                    showDeactivateOption: false
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                self.setState({
                    disablingBylines: false,
                    log: ppmaBylinesMigration.error_message + errorThrown + ' [' + textStatus + ']'
                });
            }
        });
    }

    renderDeactivatePluginOption() {
        let label = ppmaBylinesMigration.deactivate_message;
        let isEnabled = !this.state.disablingBylines;

        return (
            <PPAuthorsMaintenanceButton
                label={label}
                onClick={this.deactivateBylines}
                enabled={isEnabled}/>
        );
    }

    reset() {
        this.setState({progress: 0, inProgress: false});
    }

    renderProgressBar() {
        return (
            <PPAuthorsProgressBar value={this.state.progress}/>
        );
    }

    renderLog() {
        return (
            <PPAuthorsMaintenanceLog log={this.state.log} show={this.state.showDeactivateOption}/>
        );
    }

    render() {
        let isEnabled = !this.state.inProgress;

        let progressBar = (this.state.inProgress) ? this.renderProgressBar() : '';
        let logPanel = (this.state.log != '') ? this.renderLog() : '';
        let deactivatePluginPanel = (this.state.showDeactivateOption) ? this.renderDeactivatePluginOption() : '';

        return (
            <div>
                <PPAuthorsMaintenanceButton
                    label={ppmaBylinesMigration.copy_message}
                    onClick={this.startMigration}
                    enabled={isEnabled}/>
                {deactivatePluginPanel}
                {progressBar}
                {logPanel}
            </div>
        );
    }
}

class PPAuthorsMaintenanceButton extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        var disabled = !this.props.enabled;
        return (
            <input type="button"
                   className="button button-secondary button-danger ppma_maintenance_button"
                   onClick={this.props.onClick}
                   disabled={disabled}
                   value={this.props.label}/>
        );
    }
}

class PPAuthorsMaintenanceLog extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div>
                <div class="ppma_maintenance_log" role="status" aria-live="polite" aria-atomic="true" readOnly={true}>{this.props.log}</div>
            </div>
        );
    }
}

class PPAuthorsProgressBar extends React.Component {
    constructor(props) {
        super(props);
    }

    renderLabel() {
        return (
            <div className="p-progressbar-label">{this.props.value} %</div>
        );
    }

    render() {
        let className = 'p-progressbar p-component p-progressbar-determinate';
        let label = this.renderLabel();

        return (
            <div role="progressbar" id={this.props.id} className={className} style={this.props.style} aria-valuemin="0"
                 aria-valuenow={this.props.value} aria-valuemax="100" aria-label={this.props.value}>
                <div className="p-progressbar-value p-progressbar-value-animate"
                     style={{width: this.props.value + '%', display: 'block'}}></div>
                {label}
            </div>
        );
    }
}

jQuery(function () {
    ReactDOM.render(<PPAuthorsBylinesMigrationBox notMigratedPostsId={ppmaBylinesMigration.notMigratedPostsId}
                                                  nonce={ppmaBylinesMigration.nonce}
                                                  chunkSize={5}/>,
        document.getElementById('publishpress-authors-bylines-migration')
    );
});
