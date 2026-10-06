class PPAuthorsCoAuthorsMigrationBox extends React.Component {
    constructor(props) {
        super(props);

        this.renderDeactivatePluginOption = this.renderDeactivatePluginOption.bind(this);
        this.renderProgressBar = this.renderProgressBar.bind(this);
        this.renderLog = this.renderLog.bind(this);
        this.deactivateCoAuthorsPlus = this.deactivateCoAuthorsPlus.bind(this);
        this.startMigration = this.startMigration.bind(this);
        this.clickStart = this.clickStart.bind(this);
        this.reset = this.reset.bind(this);
        this.migrateChunkOfData = this.migrateChunkOfData.bind(this);
        this.getCoAuthorsMigrationInitialData = this.getCoAuthorsMigrationInitialData.bind(this);

        this.state = {
            totalToMigrate: 0,
            totalMigrated: 0,
            inProgress: false,
            chunkSize: this.props.chunkSize,
            progress: 0,
            log: '',
            showDeactivateOption: false,
            disablingCoAuthors: false
        };
    }

    clickStart(e) {
        e.preventDefault();

        this.startMigration();
    }

    getCoAuthorsMigrationInitialData(next) {
        var self = this;

        this.setState({
            'log': ppmaCoAuthorsMigration.start_message
        });

        window.setTimeout(() => {
            jQuery.ajax({
                type: 'GET',
                dataType: 'json',
                url: ajaxurl,
                async: false,
                data: {
                    action: 'get_coauthors_migration_data',
                    nonce: self.props.nonce
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
                        log: ppmaCoAuthorsMigration.error_message + errorThrown + ' [' + textStatus + ']',
                        showDeactivateOption: false
                    });
                }
            });
        }, 1000);
    }

    finishCoAuthorsMigration(onFinishCallBack) {
        var self = this;

        this.setState({
            progress: 99,
            'log': ppmaCoAuthorsMigration.progress_message
        });

        window.setTimeout(() => {
            jQuery.ajax({
                type: 'GET',
                dataType: 'json',
                url: ajaxurl,
                async: false,
                data: {
                    action: 'finish_coauthors_migration',
                    nonce: self.props.nonce
                },
                success: function (response) {
                    onFinishCallBack();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    self.setState({
                        progress: 0,
                        inProgress: false,
                        log: ppmaCoAuthorsMigration.error_message + errorThrown + ' [' + textStatus + ']',
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
                action: 'migrate_coauthors',
                nonce: self.props.nonce,
                chunkSize: self.state.chunkSize
            },
            success: function (response) {
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
                    self.finishCoAuthorsMigration(function () {
                        self.setState({
                            progress: 100,
                            log: ppmaCoAuthorsMigration.completed_message,
                            showDeactivateOption: true
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
                    log: ppmaCoAuthorsMigration.error_message + errorThrown + ' [' + textStatus + ']',
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
                log: ppmaCoAuthorsMigration.wait_message,
                showDeactivateOption: false
            }
        );

        window.setTimeout(() => {
            self.getCoAuthorsMigrationInitialData(() => {
                self.setState(
                    {
                        progress: 2,
                        log: ppmaCoAuthorsMigration.copying_message
                    }
                );

                self.migrateChunkOfData();
            });
        }, 1000);
    }

    deactivateCoAuthorsPlus() {
        var self = this;

        this.setState(
            {
                disablingCoAuthors: true,
                log: ppmaCoAuthorsMigration.deactivating_message
            }
        );

        jQuery.ajax({
            type: 'GET',
            dataType: 'json',
            url: ajaxurl,
            data: {
                action: 'deactivate_coauthors_plus',
                nonce: self.props.nonce
            },
            success: function (response) {
                self.setState({
                    disablingCoAuthors: false,
                    log: ppmaCoAuthorsMigration.deactivated_message,
                    showDeactivateOption: false
                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                self.setState({
                    disablingCoAuthors: false,
                    log: ppmaCoAuthorsMigration.error_message + errorThrown + ' [' + textStatus + ']'
                });
            }
        });
    }

    renderDeactivatePluginOption() {
        let label = ppmaCoAuthorsMigration.deactivate_message;
        let isEnabled = !this.state.disablingCoAuthors;

        return (
            <PPAuthorsMaintenanceButton
                label={label}
                onClick={this.deactivateCoAuthorsPlus}
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
                    label={ppmaCoAuthorsMigration.copy_message}
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
                <div class="ppma_maintenance_log" readOnly={true}>{this.props.log}</div>
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
        let progressLabel = 'Migration progress';
        let valueText = this.props.value + '% complete';

        return (
            <div role="progressbar" id={this.props.id} className={className} style={this.props.style} aria-valuemin="0"
                 aria-valuenow={this.props.value} aria-valuemax="100" aria-label={progressLabel} aria-valuetext={valueText}>
                <div className="p-progressbar-value p-progressbar-value-animate"
                     style={{width: this.props.value + '%', display: 'block'}}></div>
                {label}
            </div>
        );
    }
}

jQuery(function () {
    ReactDOM.render(<PPAuthorsCoAuthorsMigrationBox notMigratedPostsId={ppmaCoAuthorsMigration.notMigratedPostsId}
                                                    nonce={ppmaCoAuthorsMigration.nonce}
                                                    chunkSize={5}/>,
        document.getElementById('publishpress-authors-coauthors-migration')
    );
});
