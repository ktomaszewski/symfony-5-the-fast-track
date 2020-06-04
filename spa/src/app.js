import '../assets/css/app.scss';

import {h, render} from 'preact';
import {Link, Router} from 'preact-router';
import {useEffect, useState} from 'preact/hooks';

import {findConferences} from './api/api';
import Home from './pages/home';
import Conference from './pages/conference';

function App() {
    const [conferences, setConferences] = useState(null);

    useEffect(() => {
        findConferences().then((conferences) => setConferences(conferences));
    }, []);

    if (conferences === null) {
        return <div className="text-center pt-5">Loading...</div>;
    }

    return (
        <div>
            <header className="header">
                <nav className="navbar navbar-light bg-light">
                    <div className="container">
                        <Link href="/" className="navbar-brand mr-4 pr-2">&#128217; Guestbook</Link>
                    </div>
                </nav>

                <nav className="bg-light border-bottom text-center">
                    {conferences.map((conference) => (<Link href={'/conference/' + conference.slug} className="nav-conference">{conference.city} {conference.year}</Link>))}
                </nav>
            </header>

            <Router>
                <Home path="/" conferences={conferences}/>
                <Conference path="/conference/:slug" conferences={conferences}/>
            </Router>
        </div>
    );
}

render(<App/>, document.getElementById('app'));
