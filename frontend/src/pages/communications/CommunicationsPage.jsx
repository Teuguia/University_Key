import { useCallback, useEffect, useRef, useState } from 'react'
import { apiRequest } from '../../services/apiClient'

const iceServers = (() => {
  try {
    return JSON.parse(import.meta.env.VITE_WEBRTC_ICE_SERVERS || '[]')
  } catch {
    return []
  }
})()

function formatTime(value) {
  return value ? new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: 'short' }).format(new Date(value)) : ''
}

function waitForIceGathering(pc) {
  if (pc.iceGatheringState === 'complete') return Promise.resolve()
  return new Promise((resolve) => {
    const timeout = window.setTimeout(resolve, 3000)
    pc.addEventListener('icegatheringstatechange', () => {
      if (pc.iceGatheringState === 'complete') {
        window.clearTimeout(timeout)
        resolve()
      }
    }, { once: true })
  })
}

export function CommunicationsPage() {
  const [conversations, setConversations] = useState([])
  const [contacts, setContacts] = useState([])
  const [notifications, setNotifications] = useState([])
  const [selectedId, setSelectedId] = useState(null)
  const [thread, setThread] = useState([])
  const [draft, setDraft] = useState('')
  const [status, setStatus] = useState('')
  const [incoming, setIncoming] = useState(null)
  const [activeCall, setActiveCall] = useState(null)
  const peerRef = useRef(null)
  const streamRef = useRef(null)
  const remoteAudioRef = useRef(null)

  const loadSummary = useCallback(async () => {
    const [conversationPayload, contactPayload, notificationPayload, incomingPayload] = await Promise.all([
      apiRequest('/communications/conversations'),
      apiRequest('/communications/contacts'),
      apiRequest('/communications/notifications'),
      apiRequest('/communications/calls/incoming'),
    ])
    setConversations(conversationPayload.data ?? [])
    setContacts(contactPayload.data ?? [])
    setNotifications(notificationPayload.data ?? [])
    setIncoming((incomingPayload.data ?? [])[0] ?? null)
  }, [])

  const loadThread = useCallback(async (conversationId) => {
    const payload = await apiRequest(`/communications/conversations/${conversationId}`)
    setSelectedId(conversationId)
    setThread(payload.data?.messages ?? [])
  }, [])

  useEffect(() => {
    let mounted = true
    const refresh = () => loadSummary().catch((error) => {
      if (mounted) setStatus(error.message)
    })
    const initialTimer = window.setTimeout(refresh, 0)
    const timer = window.setInterval(() => loadSummary().catch(() => null), 10000)
    return () => {
      mounted = false
      window.clearTimeout(initialTimer)
      window.clearInterval(timer)
    }
  }, [loadSummary])

  useEffect(() => {
    if (!selectedId) return undefined
    let mounted = true
    const refresh = () => loadThread(selectedId).catch((error) => {
      if (mounted) setStatus(error.message)
    })
    const initialTimer = window.setTimeout(refresh, 0)
    const timer = window.setInterval(() => loadThread(selectedId).catch(() => null), 6000)
    return () => {
      mounted = false
      window.clearTimeout(initialTimer)
      window.clearInterval(timer)
    }
  }, [selectedId, loadThread])

  useEffect(() => () => cleanupCall(), [])

  useEffect(() => {
    if (!activeCall?.is_initiator || !activeCall.id || activeCall.answer) return undefined
    const timer = window.setInterval(async () => {
      try {
        const payload = await apiRequest(`/communications/calls/${activeCall.id}`)
        const call = payload.data
        if (call.answer && peerRef.current?.signalingState !== 'closed') {
          await peerRef.current.setRemoteDescription(call.answer)
          setActiveCall(call)
        } else if (['ended', 'rejected', 'missed', 'cancelled'].includes(call.status)) {
          cleanupCall()
          setActiveCall(null)
        }
      } catch { return null }
    }, 2000)
    return () => window.clearInterval(timer)
  }, [activeCall])

  function cleanupCall() {
    streamRef.current?.getTracks().forEach((track) => track.stop())
    streamRef.current = null
    peerRef.current?.close()
    peerRef.current = null
    if (remoteAudioRef.current) remoteAudioRef.current.srcObject = null
  }

  async function ensurePeer() {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false })
    const pc = new RTCPeerConnection({ iceServers })
    stream.getTracks().forEach((track) => pc.addTrack(track, stream))
    pc.ontrack = (event) => {
      if (remoteAudioRef.current) remoteAudioRef.current.srcObject = event.streams[0]
    }
    peerRef.current = pc
    streamRef.current = stream
    return pc
  }

  async function openConversation(contact) {
    try {
      const payload = await apiRequest('/communications/conversations', { method: 'POST', body: JSON.stringify({ counterpart_id: contact.id }) })
      await loadSummary()
      await loadThread(payload.data.id)
    } catch (error) { setStatus(error.message) }
  }

  async function sendMessage(event) {
    event.preventDefault()
    if (!selectedId || !draft.trim()) return
    try {
      const payload = await apiRequest(`/communications/conversations/${selectedId}/messages`, { method: 'POST', body: JSON.stringify({ content: draft.trim() }) })
      setThread((current) => [...current, payload.data])
      setDraft('')
      loadSummary().catch(() => {})
    } catch (error) { setStatus(error.message) }
  }

  async function startCall() {
    if (!selectedId) return
    try {
      setStatus('Connexion audio en cours…')
      const pc = await ensurePeer()
      const offer = await pc.createOffer()
      await pc.setLocalDescription(offer)
      await waitForIceGathering(pc)
      const payload = await apiRequest(`/communications/conversations/${selectedId}/calls`, { method: 'POST', body: JSON.stringify({ offer: pc.localDescription }) })
      setActiveCall(payload.data)
      setStatus('Appel en attente de reponse…')
    } catch (error) {
      cleanupCall()
      setStatus(error.message || 'Le microphone est indisponible.')
    }
  }

  async function acceptCall() {
    if (!incoming) return
    try {
      const pc = await ensurePeer()
      await pc.setRemoteDescription(incoming.offer)
      const answer = await pc.createAnswer()
      await pc.setLocalDescription(answer)
      await waitForIceGathering(pc)
      const payload = await apiRequest(`/communications/calls/${incoming.id}/answer`, { method: 'PATCH', body: JSON.stringify({ answer: pc.localDescription }) })
      setActiveCall(payload.data)
      setIncoming(null)
      setStatus('Appel audio connecte.')
    } catch (error) {
      cleanupCall()
      setStatus(error.message || 'Impossible d accepter cet appel.')
    }
  }

  async function finishCall(rejected = false) {
    const call = activeCall ?? incoming
    if (call) {
      try { await apiRequest(`/communications/calls/${call.id}/finish`, { method: 'PATCH', body: JSON.stringify(rejected ? { status: 'rejected' } : {}) }) } catch { setStatus('Appel termine localement.') }
    }
    cleanupCall()
    setActiveCall(null)
    setIncoming(null)
    setStatus('')
  }

  async function markAllRead() {
    await apiRequest('/communications/notifications/read-all', { method: 'PATCH' })
    setNotifications((current) => current.map((item) => ({ ...item, read_at: new Date().toISOString() })))
  }

  const selected = conversations.find((item) => item.id === selectedId)

  return (
    <section className="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
      <audio autoPlay ref={remoteAudioRef} />
      <div className="mx-auto max-w-7xl">
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div><h1 className="text-3xl font-black text-[#061d49]">Messagerie et appels</h1><p className="mt-1 text-sm font-bold text-slate-500">Echangez en prive avec votre conseiller ou vos etudiants suivis.</p></div>
          <a className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-black text-[#074fb2]" href="#dashboard">Retour au tableau de bord</a>
        </div>
        {status && <p className="mb-4 rounded-md bg-blue-50 px-4 py-3 text-sm font-bold text-blue-800">{status}</p>}
        {incoming && <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg bg-emerald-600 p-4 text-white"><span className="font-black">Appel entrant de {incoming.initiator.name}</span><span className="flex gap-2"><button className="rounded bg-white px-4 py-2 text-sm font-black text-emerald-700" onClick={acceptCall} type="button">Accepter</button><button className="rounded border border-white/50 px-4 py-2 text-sm font-black" onClick={() => finishCall(true)} type="button">Refuser</button></span></div>}
        {activeCall && <div className="mb-4 flex items-center justify-between rounded-lg bg-[#06265c] p-4 text-white"><span className="font-black">{activeCall.status === 'accepted' ? 'Appel audio en cours' : 'Appel audio en attente…'}</span><button className="rounded bg-red-500 px-4 py-2 text-sm font-black" onClick={() => finishCall()} type="button">Raccrocher</button></div>}
        <div className="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)_18rem]">
          <aside className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><h2 className="font-black text-[#061d49]">Conversations</h2><div className="mt-4 space-y-2">{conversations.map((conversation) => <button className={`w-full rounded-md p-3 text-left ${conversation.id === selectedId ? 'bg-blue-50' : 'hover:bg-slate-50'}`} key={conversation.id} onClick={() => loadThread(conversation.id)} type="button"><div className="flex justify-between gap-2"><span className="truncate font-black text-[#061d49]">{conversation.counterpart.name}</span>{conversation.unread_count > 0 && <span className="rounded-full bg-[#074fb2] px-2 text-xs font-black text-white">{conversation.unread_count}</span>}</div><p className="mt-1 truncate text-xs font-bold text-slate-500">{conversation.subject || 'Echange d orientation'}</p></button>)}</div><h2 className="mt-6 font-black text-[#061d49]">Nouveau contact</h2><div className="mt-3 space-y-2">{contacts.map((contact) => <button className="w-full rounded-md border border-slate-200 px-3 py-2 text-left text-sm font-bold text-[#074fb2] hover:bg-blue-50" key={contact.id} onClick={() => openConversation(contact)} type="button">{contact.name}</button>)}</div></aside>
          <main className="flex min-h-[34rem] flex-col rounded-lg border border-slate-200 bg-white shadow-sm">{selected ? <><header className="flex items-center justify-between border-b border-slate-100 p-4"><div><h2 className="font-black text-[#061d49]">{selected.counterpart.name}</h2><p className="text-xs font-bold text-slate-500">{selected.counterpart.role}</p></div><button className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-black text-white disabled:opacity-50" disabled={Boolean(activeCall)} onClick={startCall} type="button">Appeler</button></header><div className="flex-1 space-y-3 overflow-y-auto p-4">{thread.map((message) => <article className={`max-w-[85%] rounded-lg px-4 py-3 text-sm ${message.is_mine ? 'ml-auto bg-[#074fb2] text-white' : 'bg-slate-100 text-slate-800'}`} key={message.id}><p>{message.content}</p><p className={`mt-2 text-[11px] font-bold ${message.is_mine ? 'text-blue-100' : 'text-slate-500'}`}>{formatTime(message.sent_at)}</p></article>)}</div><form className="flex gap-2 border-t border-slate-100 p-4" onSubmit={sendMessage}><input className="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm" onChange={(event) => setDraft(event.target.value)} placeholder="Ecrivez votre message…" value={draft} /><button className="rounded-md bg-[#074fb2] px-4 py-2 text-sm font-black text-white" type="submit">Envoyer</button></form></> : <div className="grid flex-1 place-items-center p-8 text-center text-sm font-bold text-slate-500">Choisissez une conversation ou demarrez un nouvel echange.</div>}</main>
          <aside className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"><div className="flex items-center justify-between gap-2"><h2 className="font-black text-[#061d49]">Notifications</h2><button className="text-xs font-black text-[#074fb2]" onClick={markAllRead} type="button">Tout lire</button></div><div className="mt-4 space-y-3">{notifications.length ? notifications.map((notification) => <article className={`rounded-md p-3 ${notification.read_at ? 'bg-slate-50' : 'bg-amber-50'}`} key={notification.id}><h3 className="text-sm font-black text-[#061d49]">{notification.title}</h3><p className="mt-1 text-xs font-bold text-slate-600">{notification.content}</p><p className="mt-2 text-[11px] text-slate-500">{formatTime(notification.created_at)}</p></article>) : <p className="rounded-md bg-slate-50 p-3 text-sm font-bold text-slate-500">Aucune notification.</p>}</div></aside>
        </div>
      </div>
    </section>
  )
}
