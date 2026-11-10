import { useEffect, useState } from 'react'

const STORAGE_KEY = 'react-demo-notes'

function load() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) ?? []
    } catch {
        return []
    }
}

// A React app built by Vite on deploy and served as static files by Nginx.
// The notes live in this browser only (localStorage); there is no backend.
export function App() {
    const [notes, setNotes] = useState(load)
    const [text, setText] = useState('')
    const [health, setHealth] = useState(null)

    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(notes))
        } catch {
            // Private mode or storage disabled: keep notes in memory.
        }
    }, [notes])

    useEffect(() => {
        fetch('/health.json')
            .then((r) => r.json())
            .then(setHealth)
            .catch(() => setHealth({ ok: false }))
    }, [])

    const add = (event) => {
        event.preventDefault()
        if (!text.trim()) return
        setNotes([{ id: crypto.randomUUID(), text: text.trim(), done: false }, ...notes])
        setText('')
    }
    const toggle = (id) => setNotes(notes.map((n) => (n.id === id ? { ...n, done: !n.done } : n)))
    const remove = (id) => setNotes(notes.filter((n) => n.id !== id))

    return (
        <main>
            <h1>React on NanoDeploi</h1>
            <p className="muted">
                Built with Vite at {new Date(__BUILT_AT__).toLocaleString()} · health {health ? (health.ok ? 'ok' : 'failing') : 'checking…'}. Redeploy and the build time changes.
            </p>

            <form onSubmit={add}>
                <input aria-label="New note" value={text} onChange={(e) => setText(e.target.value)} placeholder="Write a note" maxLength={200} />
                <button type="submit">Add</button>
            </form>

            {notes.length === 0 ? (
                <p className="muted">No notes yet. They are saved in this browser.</p>
            ) : (
                <ul>
                    {notes.map((note) => (
                        <li key={note.id} className={note.done ? 'done' : ''}>
                            <label>
                                <input type="checkbox" checked={note.done} onChange={() => toggle(note.id)} />
                                <span>{note.text}</span>
                            </label>
                            <button type="button" className="ghost" onClick={() => remove(note.id)} aria-label={`Delete ${note.text}`}>
                                Delete
                            </button>
                        </li>
                    ))}
                </ul>
            )}
        </main>
    )
}
