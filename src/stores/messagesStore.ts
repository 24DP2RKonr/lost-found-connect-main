import * as React from "react";
import { api } from "@/lib/api";

export interface Message {
  id: string;
  text: string;
  timestamp: string;
  isOwn: boolean;
  senderName?: string;
  senderId?: string;
  read?: boolean;
}

export interface Conversation {
  id: string;
  userName: string;
  userAvatar?: string;
  lastMessage: string;
  timestamp: string;
  unread: number;
  listingId: string;
  listingTitle: string;
  messages: Message[];
  otherUserId: string;
}

let conversations: Conversation[] = [];
let listeners: (() => void)[] = [];
let currentUserId: string | null = null;

function notifyListeners() {
  listeners.forEach((l) => l());
}

function formatTime(dateStr: string): string {
  const d = new Date(dateStr);
  return `${d.getHours().toString().padStart(2, "0")}:${d.getMinutes().toString().padStart(2, "0")}`;
}

async function loadConversations(userId: string) {
  currentUserId = userId;

  let apiRows: any[] = [];
  try {
    apiRows = await api.get("/conversations");
  } catch (e) {
    console.error("Failed to load conversations", e);
    return;
  }

  conversations = (apiRows || []).map((c: any) => {
    const convMessages = Array.isArray(c.messages) ? c.messages : [];
    const lastMsg = convMessages[convMessages.length - 1];
    const other = c.other_user || {};

    const unread = convMessages.filter((m: any) => m.sender_id !== userId && !m.read).length;

    return {
      id: String(c.id),
      userName: other.name || "Lietotājs",
      userAvatar: other.avatar_url || undefined,
      lastMessage: lastMsg?.text || "",
      timestamp: lastMsg ? formatTime(lastMsg.created_at) : formatTime(c.created_at),
      unread,
      listingId: c.listing_id ? String(c.listing_id) : "",
      listingTitle: c.listing_title || "",
      otherUserId: String(other.user_id ?? ""),
      messages: convMessages.map((m: any) => ({
        id: String(m.id),
        text: m.text,
        timestamp: formatTime(m.created_at),
        isOwn: m.sender_id === userId,
        senderId: String(m.sender_id),
        read: Boolean(m.read),
      })),
    } as Conversation;
  });

  notifyListeners();
}

export const messagesStore = {
  getConversations: (): Conversation[] => conversations,

  getConversation: (id: string): Conversation | undefined => {
    return conversations.find((c) => c.id === id);
  },

  loadForUser: loadConversations,

  startConversation: async (data: {
    listingId: string;
    listingTitle: string;
    messageText: string;
    authorName?: string;
    receiverId: string;
  }): Promise<Conversation | null> => {
    if (!currentUserId) return null;

    // Check if conversation already exists for this listing between these users
    const existing = conversations.find(
      (c) => c.listingId === data.listingId && c.otherUserId === data.receiverId
    );

    if (existing) {
      await messagesStore.addMessage(existing.id, data.messageText);
      return conversations.find((c) => c.id === existing.id) || null;
    }

    let conv: any;
    try {
      conv = await api.post("/conversations", {
        listing_id: data.listingId ? Number(data.listingId) : null,
        listing_title: data.listingTitle,
        receiver_id: Number(data.receiverId),
      });
    } catch (e) {
      console.error("Failed to create conversation", e);
      return null;
    }

    let msg: any = null;
    try {
      msg = await api.post(`/conversations/${conv.id}/messages`, { text: data.messageText });
    } catch (e) {
      console.error("Failed to send first message", e);
    }

    const newConv: Conversation = {
      id: String(conv.id),
      userName: data.authorName || "Lietotājs",
      listingId: data.listingId,
      listingTitle: data.listingTitle,
      lastMessage: data.messageText,
      timestamp: msg ? formatTime(msg.created_at) : formatTime(conv.created_at),
      unread: 0,
      otherUserId: data.receiverId,
      messages: msg
        ? [
            {
              id: String(msg.id),
              text: msg.text,
              timestamp: formatTime(msg.created_at),
              isOwn: true,
              senderId: currentUserId,
            },
          ]
        : [],
    };

    conversations = [newConv, ...conversations];
    notifyListeners();
    return newConv;
  },

  addMessage: async (conversationId: string, text: string): Promise<void> => {
    if (!currentUserId) return;

    let msg: any;
    try {
      msg = await api.post(`/conversations/${conversationId}/messages`, { text });
    } catch (e) {
      console.error("Failed to send message", e);
      return;
    }

    const newMsg: Message = {
      id: String(msg.id),
      text: msg.text,
      timestamp: formatTime(msg.created_at),
      isOwn: true,
      senderId: currentUserId,
    };

    conversations = conversations.map((c) => {
      if (c.id === conversationId) {
        return {
          ...c,
          messages: [...c.messages, newMsg],
          lastMessage: text,
          timestamp: newMsg.timestamp,
        };
      }
      return c;
    });

    notifyListeners();
  },

  markAsRead: async (conversationId: string): Promise<void> => {
    if (!currentUserId) return;

    // Optimistic UI update first
    conversations = conversations.map((c) => {
      if (c.id !== conversationId) return c;

      return {
        ...c,
        unread: 0,
        messages: c.messages.map((m) =>
          m.isOwn ? m : { ...m, read: true }
        ),
      };
    });
    notifyListeners();

    try {
      await api.put(`/conversations/${conversationId}/read`, {});
    } catch (e) {
      console.error("Failed to mark conversation as read", e);
      // If it fails, reload from server so badge doesn't get stuck wrong.
      if (currentUserId) await loadConversations(currentUserId);
    }
  },

  subscribe: (listener: () => void): (() => void) => {
    listeners.push(listener);
    return () => {
      listeners = listeners.filter((l) => l !== listener);
    };
  },
};

// Supabase realtime removed (Laravel-backed)

export function useConversations(): Conversation[] {
  const [, forceUpdate] = React.useState({});

  React.useEffect(() => {
    return messagesStore.subscribe(() => forceUpdate({}));
  }, []);

  return messagesStore.getConversations();
}
