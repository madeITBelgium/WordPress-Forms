import { useEffect, useState } from '@wordpress/element';
import { SelectControl, Placeholder, Button, Spinner, Modal, Flex, FlexItem, Toolbar, ToolbarButton } from '@wordpress/components';
import { useBlockProps, BlockControls } from '@wordpress/block-editor';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit({ attributes, setAttributes }) {
	const { formId } = attributes;
	const [forms, setForms] = useState([]);
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);
	const [isOpen, setOpen] = useState(false);

    const blockProps = useBlockProps( { } );
    const toolbarButtons = [];
   

    useEffect(() => {
		let isMounted = true;
		apiFetch({ path: '/madeit/v1/forms' })
			.then((data) => {
				if (!isMounted) return;
				setForms(Array.isArray(data) ? data : []);
				setError(null);
			})
			.catch((e) => {
				if (!isMounted) return;
				setForms([]);
				setError(e);
			})
			.finally(() => {
				if (!isMounted) return;
				setLoading(false);
			});

		return () => {
			isMounted = false;
		};
	}, []);

	if (loading) {
		return <Spinner />;
	}

    if (!formId) {
        return (
            <div { ...blockProps }>
                <Placeholder label="Made I.T. Form">
                    {error && (
                        <p style={{ marginTop: 0 }}>
                            Kon formulieren niet ophalen. Controleer of je bent ingelogd en of de plugin API bereikbaar is.
                        </p>
                    )}
                    <SelectControl
                        label="Selecteer formulier"
                        value={formId}
                        options={[
                            { label: 'Kies een formulier', value: 0 },
                            ...forms.map((form) => ({
                                label: form.name,
                                value: form.id,
                            })),
                        ]}
                        onChange={(value) => setAttributes({ formId: parseInt(value, 10) })}
                    />

                    {/* Nieuw formulier aanmaken */}
                    <Button
                        variant="primary"
                        onClick={() => {

                            apiFetch({
                                path: '/madeit/v1/forms',
                                method: 'POST',
                            })
                            .then((data) => {

                                if (!data || !data.id) {
                                    throw new Error();
                                }

                                // Nieuwe form ID instellen
                                setAttributes({ formId: data.id });

                                // Modal openen
                                setOpen(true);

                            })
                            .catch(() => {
                                alert('Er is een fout opgetreden bij het aanmaken van het formulier.');
                            });

                        }}
                    >
                        Nieuw formulier
                    </Button>

                    {isOpen && (
                        <Modal className="formsModalMadeit" title="Formulier bewerken" onRequestClose={() => setOpen(false)}>
                            <iframe
                                src={`/wp-admin/post.php?post=${formId}&action=edit`}
                                style={{ width: '100%' }}
                            />
                        </Modal>
                    )}
                </Placeholder>
            </div>
        );
    }

    if (formId) {
        toolbarButtons.push(
            <>
                <ToolbarButton
                    key="edit"
                    icon="edit"
                    label="Bewerk formulier"
                    text="Formulier bewerken"
                    onClick={() => setOpen(true)}
                />

                <ToolbarButton
                    key="new"
                    icon="plus"
                    label="Ander formulier selecteren"
                    text="Ander formulier selecteren"
                    onClick={() => setAttributes({ formId: null })}
                />
            </>
        );
    }

    return (
        <div { ...blockProps }>
            <BlockControls>
                {toolbarButtons}
            </BlockControls>
            <div className="madeit-form-preview-wrapper">

                {/* LIVE PREVIEW */}

                {/* If modal had closed then rerender the form preview */}
                { !isOpen && (
                    <ServerSideRender
                        block="madeit/forms"
                        attributes={{ formId }}
                    />
                )}
                

                {isOpen && (
                    <Modal
                        className="formsModalMadeit"
                        title="Formulier bewerken"
                        onRequestClose={() => setOpen(false)}
                    >
                        <iframe
                            src={`/wp-admin/post.php?post=${formId}&action=edit`}
                            style={{ width: '100%'}}
                        />
                    </Modal>
                )}

            </div>
        </div>
    );
}
