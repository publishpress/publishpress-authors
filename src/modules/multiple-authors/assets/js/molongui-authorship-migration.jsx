class PPAuthorsMolonguiAuthorshipMigrationBox extends React.Component {
    constructor(props) {
        super(props);

        this.state = {
            step: 'form',
            log: '',
            total: 0,
            migrated: 0,
            progress: 0,
            buttonEnabled: true,
        };

        this.startMigration = this.startMigration.bind(this);
        this.deactivateMolongui = this.deactivateMolongui.bind(this);
        this.migrateMolonguiData = this.migrateMolonguiData.bind(this);
    }

    componentDidMount() {
        this.getMigrationData();
    }

    getMigrationData() {
        jQuery.get(
            ajaxurl,
            {
                action: 'get_molongui_authorship_migration_data',
                nonce: this.props.nonce,
            },
            (response) => {
                this.setState({
                    total: response.total,
                });
            }
        );
    }

    startMigration() {
        this.setState({
            step: 'migration',
            log: ppmaMolonguiAuthorshipMigration.start_message,
            buttonEnabled: false,
        });

        this.migrateMolonguiData();
    }

    migrateMolonguiData() {
        jQuery.get(
            ajaxurl,
            {
                action: 'migrate_molongui_authorship',
                nonce: this.props.nonce,
            },
            (response) => {
                if (response.success) {
                    const remaining = response.total;
                    const migrated = this.state.total - remaining;
                    const progress = this.state.total > 0 ? Math.round((migrated / this.state.total) * 100) : 100;

                    this.setState({
                        migrated: migrated,
                        progress: progress,
                        log: 'Migrating data... (' + migrated + '/' + this.state.total + ')',
                    });

                    if (remaining > 0) {
                        setTimeout(() => {
                            this.migrateMolonguiData();
                        }, 500);
                    } else {
                        this.setState({
                            step: 'completed',
                            log: ppmaMolonguiAuthorshipMigration.completed_message,
                            buttonEnabled: true,
                        });
                    }
                } else {
                    this.setState({
                        log: ppmaMolonguiAuthorshipMigration.error_message + response.error,
                        buttonEnabled: true,
                    });
                }
            }
        ).fail((xhr, status, error) => {
            this.setState({
                log: ppmaMolonguiAuthorshipMigration.error_message + error,
                buttonEnabled: true,
            });
        });
    }

    deactivateMolongui() {

        if (this.deactivationInProgress) {
            return;
        }

        this.deactivationInProgress = true;

        this.setState({
            log: ppmaMolonguiAuthorshipMigration.deactivating_message,
            buttonEnabled: false,
        });

        jQuery.get(
            ajaxurl,
            {
                action: 'deactivate_molongui_authorship',
                nonce: this.props.nonce,
            },
            (response) => {
                this.deactivationInProgress = false;

                if (response.success) {
                    this.setState({
                        log: ppmaMolonguiAuthorshipMigration.deactivated_message,
                        buttonEnabled: true,
                    });
                } else {
                    this.setState({
                        log: ppmaMolonguiAuthorshipMigration.error_message + 'Failed to deactivate plugin',
                        buttonEnabled: true,
                    });
                }
            }
        ).fail((xhr, status, error) => {
            this.deactivationInProgress = false;
            this.setState({
                log: ppmaMolonguiAuthorshipMigration.error_message + error,
                buttonEnabled: true,
            });
        });
    }

    render() {
        if (this.state.step === 'form') {
            return (
                <div>
                    <p>Total items to migrate: <strong>{this.state.total}</strong></p>
                    <PPAuthorsMaintenanceButton
                        onClick={this.startMigration}
                        enabled={this.state.buttonEnabled}
                        label={ppmaMolonguiAuthorshipMigration.copy_message}
                    />
                </div>
            );
        }

        if (this.state.step === 'migration') {
            return (
                <div>
                    <PPAuthorsProgressBar value={this.state.progress} />
                    <PPAuthorsMaintenanceLog log={this.state.log} />
                </div>
            );
        }

        if (this.state.step === 'completed') {
            return (
                <div>
                    <PPAuthorsMaintenanceLog log={this.state.log} />
                    <PPAuthorsMaintenanceButton
                        onClick={this.deactivateMolongui}
                        enabled={this.state.buttonEnabled}
                        label={ppmaMolonguiAuthorshipMigration.deactivate_message}
                    />
                </div>
            );
        }

        return null;
    }
}

class PPAuthorsMaintenanceButton extends React.Component {
    render() {
        var disabled = !this.props.enabled;
        return (
            <input type="button"
                className="button button-secondary button-danger ppma_maintenance_button"
                onClick={this.props.onClick}
                disabled={disabled}
                value={this.props.label} />
        );
    }
}

class PPAuthorsMaintenanceLog extends React.Component {
    render() {
        return (
            <div>
                <div className="ppma_maintenance_log" readOnly={true}>{this.props.log}</div>
            </div>
        );
    }
}

class PPAuthorsProgressBar extends React.Component {
    render() {
        let className = 'p-progressbar p-component p-progressbar-determinate';
        let label = <div className="p-progressbar-label">{this.props.value} %</div>;

        return (
            <div role="progressbar" className={className} aria-valuemin="0"
                aria-valuenow={this.props.value} aria-valuemax="100">
                <div className="p-progressbar-value p-progressbar-value-animate"
                    style={{ width: this.props.value + '%', display: 'block' }}></div>
                {label}
            </div>
        );
    }
}

jQuery(function () {
    ReactDOM.render(<PPAuthorsMolonguiAuthorshipMigrationBox nonce={ppmaMolonguiAuthorshipMigration.nonce} />,
        document.getElementById('publishpress-authors-molongui-authorship-migration')
    );
});