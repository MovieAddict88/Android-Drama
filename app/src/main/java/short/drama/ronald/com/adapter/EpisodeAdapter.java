package short.drama.ronald.com.adapter;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.bumptech.glide.Glide;

import short.drama.ronald.com.R;
import short.drama.ronald.com.model.Episode;

import java.util.ArrayList;
import java.util.List;

public class EpisodeAdapter extends RecyclerView.Adapter<EpisodeAdapter.ViewHolder> {

    public interface OnEpisodeClickListener {
        void onEpisodeClick(Episode episode, int position);
    }

    private final List<Episode> items = new ArrayList<>();
    private OnEpisodeClickListener listener;
    private int currentPlayingIndex = -1;

    public void setOnEpisodeClickListener(OnEpisodeClickListener listener) {
        this.listener = listener;
    }

    public void setData(List<Episode> episodes) {
        items.clear();
        if (episodes != null) items.addAll(episodes);
        notifyDataSetChanged();
    }

    public void setCurrentPlaying(int index) {
        int prev = currentPlayingIndex;
        currentPlayingIndex = index;
        if (prev >= 0) notifyItemChanged(prev);
        if (index >= 0) notifyItemChanged(index);
    }

    public Episode getItem(int position) {
        if (position >= 0 && position < items.size()) return items.get(position);
        return null;
    }

    public int getCount() {
        return items.size();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View v = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_episode, parent, false);
        return new ViewHolder(v);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Episode episode = items.get(position);
        holder.tvEpisodeName.setText(episode.getChapterName());

        if (episode.isCharged()) {
            holder.tvLock.setVisibility(View.VISIBLE);
        } else {
            holder.tvLock.setVisibility(View.GONE);
        }

        if (position == currentPlayingIndex) {
            holder.itemView.setBackgroundResource(R.drawable.bg_episode_active);
        } else {
            holder.itemView.setBackgroundResource(R.drawable.bg_episode_normal);
        }

        if (episode.getChapterImg() != null && !episode.getChapterImg().isEmpty()) {
            Glide.with(holder.itemView.getContext())
                    .load(episode.getChapterImg())
                    .placeholder(R.drawable.ic_placeholder)
                    .centerCrop()
                    .into(holder.ivThumb);
        } else {
            holder.ivThumb.setImageResource(R.drawable.ic_placeholder);
        }

        holder.itemView.setOnClickListener(v -> {
            if (listener != null) listener.onEpisodeClick(episode, position);
        });
    }

    @Override
    public int getItemCount() {
        return items.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView ivThumb;
        TextView tvEpisodeName;
        TextView tvLock;

        ViewHolder(@NonNull View itemView) {
            super(itemView);
            ivThumb = itemView.findViewById(R.id.iv_thumb);
            tvEpisodeName = itemView.findViewById(R.id.tv_episode_name);
            tvLock = itemView.findViewById(R.id.tv_lock);
        }
    }
}
