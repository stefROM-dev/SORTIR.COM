"use strict";

// @todo: Get the current user.
const partipant = null;

const Sortie = ({data}) => {

    const headers = [
        {label: "Nom de la sortie", classes: "col-4 col-lg-2"},
        {label: "Date de la sortie", classes: "col-4 col-lg-2"},
        {label: "Clôture", classes: "d-none d-lg-flex col-lg-2"},
        {label: "Inscrits/Places", classes: "d-none d-lg-flex col-lg-1"},
        {label: "Etat", classes: "d-none d-lg-flex col-lg-1"},
        {label: "Inscrit", classes: "d-none d-lg-flex col-lg-1"},
        {label: "Organisateur", classes: "d-none d-lg-flex col-lg-1"},
        {label: "Lieu", classes: "d-flex d-lg-none col-4"}
    ]

    const [sorties, setSorties] = React.useState(data);

    const get = (nom = "",
                 campus = "",
                 debut = null,
                 fin = null,
                 isOrganisateur = false,
                 isInscrit = false,
                 isFinie = false) => {
        const parameters = {nom, campus, debut, fin, isOrganisateur, isInscrit, isFinie};
        let endpoint = "/sorties/api?"
        for (let [pointer, value] of Object.entries(parameters)) {
            if (value) { endpoint += `${pointer}=${value}&`; }
        }
        Ajax.get(endpoint).then(campus => {
            setSorties(campus);
        });
    }

    const hydrate = (sortie = null, insert = false) => {
        // @todo: Get the list of participants.
        sortie.participation = `${sortie.participants}/${sortie.nbInscriptionsMax}`;
        sortie.isInscrit = sortie.participants.includes(partipant) && "X";
        sortie.isOrganisateur = sortie.organisateur === partipant;
        sortie.columns = [
            sortie.nom,
            sortie.dateHeureDebut,
            sortie.dateLimiteInscription,
            sortie.participation,
            sortie.etat,
            sortie.isInscrit,
            sortie.organisateur
        ]
        sortie.actions = { classes: "d-none d-lg-flex col-lg-2 input-group mb-3 row" };
        sortie.buttons = [
            {
                value: new Proxy({"Créée": "Modifier"}, {
                    get: (target, property) =>
                        target.hasOwnProperty(property) ? target[property] : "Afficher"
                }),
                classes: {"true": "btn btn-info col-5", "false": "btn btn-warning col-5"}
            }]
        !["En cours", "Clôturée", "Passée"].includes(sortie.etat) &&
        sorties.buttons.push(
            {
                value: {
                    "Ouverte": (
                        sortie.isOrganisateur ? "Annuler" :
                        sortie.inscrit ? "Se désister" : "S'inscrire"
                    ),
                    "Créée" : "Publier"
                },
                classes: {"true" : "btn btn-danger col-5 offset-1", "false": "btn btn-danger col-5 offset-1"},
            }
        );
        return sortie;
    }

    return (
        <div>
            <SearchBar onChange={get} />
            <Table data={sorties} headers={headers} hydrate={hydrate} addLine={false} />
        </div>
    );
}

Ajax.get("/sorties/api").then(sorties => {
    ReactDOM.render(
        <Sortie data={sorties}/>,
        document.querySelector("#table")
    );
});